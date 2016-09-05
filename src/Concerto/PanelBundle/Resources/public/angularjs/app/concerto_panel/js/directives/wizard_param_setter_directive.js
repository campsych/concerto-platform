angular.module('concertoPanel').directive('wizardParamSetter', ["$compile", "$templateCache", "$uibModal", "$filter", "uiGridConstants", "GridService", "RDocumentation", "$http", "DataTableCollectionService", "TestCollectionService", "ViewTemplateCollectionService", "TestWizardParam", function ($compile, $templateCache, $uibModal, $filter, uiGridConstants, GridService, RDocumentation, $http, DataTableCollectionService, TestCollectionService, ViewTemplateCollectionService, TestWizardParam) {
        return {
            restrict: 'E',
            scope: {
                param: "=",
                output: "=",
                values: "=",
                parent: "=",
                wizardObject: "=",
                underList: "="
            },
            link: function (scope, element, attrs, controllers) {
                scope.RDocumentation = RDocumentation;
                scope.testWizardParamService = TestWizardParam;
                scope.gridService = GridService;
                scope.dataTableCollectionService = DataTableCollectionService;
                scope.testCollectionService = TestCollectionService;
                scope.viewTemplateCollectionService = ViewTemplateCollectionService;
                scope.htmlEditorOptions = Defaults.ckeditorTestContentOptions;

                scope.codeEditorOptions = {
                    lineWrapping: true,
                    lineNumbers: true,
                    mode: 'r',
                    viewportMargin: Infinity,
                    hintOptions: {
                        completeSingle: false,
                        wizardService: RDocumentation
                    },
                    extraKeys: {
                        "F11": function (cm) {
                            cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                        },
                        "Esc": function (cm) {
                            if (cm.getOption("fullScreen"))
                                cm.setOption("fullScreen", false);
                        },
                        "Ctrl-Space": "autocomplete"
                    }
                };
                if (RDocumentation.functionIndex === null) {
                    $http.get(RDocumentation.rCacheDirectory + 'functionIndex.json').success(function (data) {
                        if (data !== null) {
                            RDocumentation.functionIndex = data;
                            scope.codeEditorOptions.hintOptions.functionIndex = data;
                        }
                    });
                } else {
                    scope.codeEditorOptions.hintOptions.functionIndex = RDocumentation.functionIndex;
                }

                scope.mode = "dialog";
                scope.wizardMode = "prod";
                scope.complexSetters = [1, 2, 7, 9, 10, 11];
                scope.isSetterComplex = false;
                scope.title = "";
                scope.summary = "";
                if ("mode" in attrs) {
                    scope.mode = attrs.mode;
                }
                if ("wizardMode" in attrs) {
                    scope.wizardMode = attrs.wizardMode;
                }

                scope.onPrimitiveValueChange = function (value) {
                    scope.output = value;
                    if (scope.wizardMode == "dev" && value != null && !scope.underList) {
                        scope.param.definition.defvalue = value;
                    }
                    if (scope.parent === null)
                        scope.values[scope.param.name] = value;
                };

                scope.updateSeterComplexity = function () {
                    scope.isSetterComplex = scope.complexSetters.indexOf(parseInt(scope.param.type)) !== -1;
                };
                scope.updateTitle = function () {
                    scope.title = scope.testWizardParamService.getSetterTitle(scope.param);
                };
                scope.updateSummary = function () {
                    scope.summary = scope.testWizardParamService.getSetterSummary(scope.param, scope.output);
                };
                scope.listOptions = {
                    enableFiltering: true,
                    enableGridMenu: true,
                    exporterMenuCsv: false,
                    exporterMenuPdf: false,
                    importerShowMenu: false,
                    data: "output",
                    exporterCsvFilename: 'export.csv',
                    exporterHeaderFilterUseName: true,
                    exporterHeaderFilter: function (name) {
                        return name;
                    },
                    showGridFooter: true,
                    columnDefs: [],
                    onRegisterApi: function (gridApi) {
                        scope.listGridApi = gridApi;
                    },
                    importerDataAddCallback: function (grid, newObjects) {
                        for (var i = 0; i < newObjects.length; i++) {
                            for (var key in newObjects[i]) {
                                for (var j = 0; j < scope.listOptions.columnDefs.length; j++) {
                                    var col = scope.listOptions.columnDefs[j];
                                    if (col.name === key) {
                                        if (col.type == 4) {
                                            newObjects[i][key] = newObjects[i][key].toString();
                                        }
                                        if (col.type == 9 || col.type == 10) {
                                            newObjects[i][key] = angular.fromJson(newObjects[i][key]);
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                        scope.output = scope.output.concat(newObjects);
                    },
                    exporterFieldCallback: function (grid, row, col, value) {
                        if (value.constructor == Array) {
                            value = angular.toJson(value);
                        }
                        return value;
                    },
                    enableCellEditOnFocus: true
                };
                scope.$watch("output.length", function (newValue) {
                    scope.listOptions.enableFiltering = newValue > 0;
                    if (scope.listGridApi && uiGridConstants.dataChange) {
                        scope.listGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                    }
                });
                scope.getColumnDefs = function (obj, param, parent, output, isGroupField) {
                    if (!obj)
                        return [];
                    if (!isGroupField && obj.type == 9) {
                        var fields = [];
                        for (var i = 0; i < obj.definition.fields.length; i++) {
                            var field = obj.definition.fields[i];
                            var param = "grid.appScope.param.definition.element.definition.fields[" + i + "]";
                            var parent = "grid.appScope.output[grid.renderContainers.body.visibleRowCache.indexOf(row)]";
                            var output = "grid.appScope.output[grid.renderContainers.body.visibleRowCache.indexOf(row)][grid.appScope.param.definition.element.definition.fields[" + i + "].name]";
                            var add = scope.getColumnDefs(field, param, parent, output, true);
                            for (var j = 0; j < add.length; j++) {
                                fields.push(add[j]);
                            }
                        }
                        return fields;
                    }

                    return [{
                            type: obj.type,
                            displayName: isGroupField ? obj.label : Trans.TEST_WIZARD_PARAM_LIST_COLUMN_ELEMENT,
                            name: isGroupField ? obj.name : "element",
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents'>" +
                                    scope.getParamSetterCellTemplate(param, parent, output) +
                                    "</div>"
                        }];
                };
                scope.getParamSetterCellTemplate = function (param, parent, output) {
                    var cell = '<wizard-param-setter param="' + param + '" parent="' + parent + '" output="' + output + '" mode="grid" wizard-mode="' + scope.wizardMode + '" under-list="true" values="grid.appScope.values" wizard-object="grid.appScope.wizardObject"></wizard-param-setter>';
                    return cell;
                };
                scope.initializeListColumnDefs = function () {
                    var defs = [];
                    var param = "grid.appScope.param.definition.element";
                    var parent = "grid.appScope.output";
                    var output = "grid.appScope.output[grid.renderContainers.body.visibleRowCache.indexOf(row)].value";
                    var cd = scope.getColumnDefs(scope.param.definition.element, param, parent, output, false);
                    for (var i = 0; i < cd.length; i++) {
                        defs.push(cd[i]);
                    }

                    defs.push({
                        displayName: "",
                        name: "_action",
                        enableSorting: false,
                        enableFiltering: false,
                        exporterSuppressExport: true,
                        enableCellEdit: false,
                        cellTemplate:
                                "<div class='ui-grid-cell-contents' align='center'>" +
                                '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeElement(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_LIST_ELEMENT_DELETE + '</button>' +
                                "</div>",
                        width: 100
                    });
                    scope.listOptions.columnDefs = defs;
                };
                scope.launchSetterDialog = function (param, output, parent, values, wizardObject) {
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_setter_dialog.html",
                        scope: scope,
                        controller: TestWizardParamSetterController,
                        resolve: {
                            param: function () {
                                return param;
                            },
                            output: function () {
                                return output;
                            },
                            parent: function () {
                                return parent;
                            },
                            values: function () {
                                return values;
                            },
                            wizardObject: function () {
                                return wizardObject;
                            },
                            wizardMode: function () {
                                return scope.wizardMode;
                            }
                        },
                        size: "prc-lg"
                    });
                    modalInstance.result.then(function (result) {
                        scope.output = result;
                    }, function () {
                    });
                };
                scope.moveElementUp = function (index) {
                    scope.output.splice(index + 1, 0, scope.output.splice(index, 1)[0]);
                };
                scope.moveElementDown = function (index) {
                    scope.output.splice(index - 1, 0, scope.output.splice(index, 1)[0]);
                };
                scope.moveFieldUp = function (index) {
                    var params = $filter('orderBy')(scope.param.definition.fields, "order");
                    var paramFound = false;
                    for (var i = 0; i < params.length; i++) {
                        var param = params[i];
                        if (param.order !== i) {
                            param.order = i;
                        }
                        if (paramFound) {
                            param.order--;
                            params[i - 1].order++;
                            paramFound = false;
                        }
                        if (index === i) {
                            paramFound = true;
                        }
                    }
                };
                scope.moveFieldDown = function (index) {
                    var params = $filter('orderBy')(scope.param.definition.fields, "order");
                    for (var i = 0; i < params.length; i++) {
                        var param = params[i];
                        if (param.order !== i) {
                            param.order = i;
                        }
                        if (index === i) {
                            params[i - 1].order++;
                            param.order--;
                        }
                    }
                };
                scope.addElement = function () {
                    if (scope.param.definition.element.type == 4) {
                        scope.output.push({value: null});
                    } else if (scope.param.definition.element.type == 7 || scope.param.definition.element.type == 9) {
                        scope.output.push({});
                    } else if (scope.param.definition.element.type == 10) {
                        scope.output.push([]);
                    } else {
                        scope.output.push({value: null});
                    }
                };
                scope.removeElement = function (index) {
                    scope.output.splice(index, 1);
                };
                scope.removeSelectedElements = function () {
                    var selectedRows = scope.listGridApi.selection.getSelectedRows();
                    var rows = scope.listGridApi.grid.rows;
                    for (var i = 0; i < selectedRows.length; i++) {
                        for (var j = 0; j < rows.length; j++) {
                            if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
                                scope.removeElement(j);
                                break;
                            }
                        }
                    }
                };
                scope.removeAllElements = function () {
                    scope.output = [];
                };
                scope.$watch('param.type', function (newValue, oldValue) {
                    if (!scope.param)
                        return;
                    if (newValue === null || newValue === undefined)
                        return;

                    switch (parseInt(newValue)) {
                        case 7:
                        case 9:
                            if (scope.output === null || scope.output === undefined || typeof scope.output !== 'object' || scope.output.constructor === Array || newValue != oldValue) {
                                scope.output = {};
                            }
                            break;
                        case 10:
                            if (scope.output === null || scope.output === undefined || scope.output.constructor !== Array || newValue != oldValue) {
                                scope.output = [];
                            }
                            break;
                        default:
                            if (scope.output === undefined || typeof scope.output === 'object' || newValue != oldValue) {
                                scope.output = null;
                            }
                            break;
                    }

                    if (newValue == 10) {
                        scope.initializeListColumnDefs();
                    }
                    scope.updateSeterComplexity();
                    scope.updateTitle();
                    scope.updateSummary();
                    element.html($templateCache.get("type_" + newValue + "_setter.html"));
                    $compile(element.contents())(scope);
                });

                scope.$watch('param.definition.element.type', function (newValue, oldValue) {
                    if (newValue === null || newValue === undefined)
                        return;
                    if (newValue != oldValue) {
                        if (scope.param.type == 10) {
                            if (scope.output === null || scope.output === undefined || scope.output.constructor !== Array || newValue !== oldValue) {
                                scope.output = [];
                            }
                        }
                    }
                });
                scope.$watchCollection('output', function (newValue) {
                    scope.updateSummary();
                });
                scope.$watch("param.definition.defvalue", function (newValue, oldValue) {
                    if (scope.output === null || (scope.wizardMode == "dev" && newValue != null && newValue != undefined && !scope.underList)) {
                        scope.output = newValue;
                    }
                });
            }
        };
    }]);