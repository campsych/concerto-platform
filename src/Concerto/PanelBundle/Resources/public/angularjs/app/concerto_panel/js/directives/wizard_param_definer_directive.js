angular.module('concertoPanel').directive('wizardParamDefiner', ["$compile", "$templateCache", "$uibModal", "uiGridConstants", "TestWizardParam", "GridService", "RDocumentation", function ($compile, $templateCache, $uibModal, uiGridConstants, TestWizardParam, GridService, RDocumentation) {
        return {
            restrict: 'E',
            scope: {
                param: "=",
                typesCollection: "=types"
            },
            link: function (scope, element, attrs, controllers) {
                scope.htmlEditorOptions = Defaults.ckeditorPanelContentOptions;
                scope.testWizardParamService = TestWizardParam;
                scope.gridService = GridService;
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

                scope.hasCustomDefiner = function () {
                    if (!scope.param)
                        return false;
                    return scope.typesCollection[scope.param.type].definer;
                };

                if (scope.param) {
                    if (!("definition" in scope.param)) {
                        scope.param.definition = {placeholder: 0};
                    }
                }

                scope.selectOptions = {
                    enableFiltering: true,
                    enableGridMenu: true,
                    exporterMenuCsv: false,
                    exporterMenuPdf: false,
                    importerShowMenu: false,
                    data: 'param.definition.options',
                    exporterCsvFilename: 'export.csv',
                    showGridFooter: true,
                    columnDefs: [
                        {
                            displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_VALUE,
                            field: "value"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_LABEL,
                            field: "label"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_ORDER,
                            type: "number",
                            field: "order"
                        }, {
                            displayName: "",
                            name: "_action",
                            enableSorting: false,
                            enableFiltering: false,
                            enableCellEdit: false,
                            exporterSuppressExport: true,
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents' align='center'>" +
                                    '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeOption(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_SELECT_LIST_BUTTON_DELETE + '</button>' +
                                    "</div>",
                            width: 100
                        }
                    ],
                    onRegisterApi: function (gridApi) {
                        scope.selectGridApi = gridApi;
                    },
                    importerDataAddCallback: function (gridApi, newObjects) {
                        scope.param.definition.options = scope.param.definition.options.concat(newObjects);
                    },
                    enableCellEditOnFocus: true
                };

                scope.$watch("param.definition.options.length", function (newValue) {
                    scope.selectOptions.enableFiltering = newValue > 0;
                    if (scope.selectGridApi && uiGridConstants.dataChange) {
                        scope.selectGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                    }
                });

                scope.groupOptions = {
                    enableFiltering: true,
                    enableGridMenu: true,
                    exporterMenuCsv: false,
                    exporterMenuPdf: false,
                    data: "param.definition.fields",
                    exporterCsvFilename: 'export.csv',
                    showGridFooter: true,
                    columnDefs: [
                        {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_NAME,
                            field: "name"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_LABEL,
                            field: "label"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_TYPE,
                            field: "type",
                            editableCellTemplate: 'ui-grid/dropdownEditor',
                            editDropdownOptionsArray: scope.typesCollection,
                            editDropdownIdLabel: "id",
                            editDropdownValueLabel: "label",
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents'>" +
                                    "{{grid.appScope.typesCollection[row.entity.type].label}}" +
                                    "</div>"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_HIDE_CONDITION,
                            field: "hideCondition"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_DEFINITION,
                            field: "definition",
                            enableCellEdit: false,
                            enableSorting: false,
                            enableFiltering: false,
                            exporterSuppressExport: true,
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents' bind-html-compile='grid.appScope.getParamDefinitionCellTemplate(row.entity)'>" +
                                    "</div>"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_ORDER,
                            type: "number",
                            field: "order"
                        }, {
                            displayName: "",
                            name: "_action",
                            enableSorting: false,
                            enableFiltering: false,
                            enableCellEdit: false,
                            exporterSuppressExport: true,
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents' align='center'>" +
                                    '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeField(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_GROUP_LIST_BUTTON_DELETE + '</button>' +
                                    "</div>",
                            width: 100
                        }
                    ],
                    onRegisterApi: function (gridApi) {
                        scope.groupGridApi = gridApi;
                    },
                    enableCellEditOnFocus: true
                };

                scope.$watch("param.definition.fields.length", function (newValue) {
                    scope.groupOptions.enableFiltering = newValue > 0;
                    if (scope.groupGridApi && uiGridConstants.dataChange) {
                        scope.groupGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                    }
                });

                scope.getParamDefinitionCellTemplate = function (param) {
                    var cell = "";
                    if (scope.typesCollection[param.type].definer) {
                        cell = "<i class='glyphicon glyphicon-align-justify clickable' ng-click='grid.appScope.launchDefinitionDialog(row.entity)' uib-tooltip-html='\"" + Trans.TEST_WIZARD_PARAM_DEFINITION_ICON_TOOLTIP + "\"' tooltip-append-to-body='true'></i>" +
                                '<span class="wizardParamSummary">{{grid.appScope.testWizardParamService.getDefinerSummary(row.entity)}}</span>';
                    } else
                        cell = "-";
                    return cell;
                };

                scope.addOption = function () {
                    if (!("options" in scope.param.definition))
                        scope.param.definition.options = [];
                    scope.param.definition.options.push({
                        value: "",
                        label: ""
                    });
                };

                scope.deleteSelectedOptions = function () {
                    var selectedRows = scope.selectGridApi.selection.getSelectedRows();
                    var rows = scope.selectGridApi.grid.rows;
                    for (var i = 0; i < selectedRows.length; i++) {
                        for (var j = 0; j < rows.length; j++) {
                            if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
                                scope.removeOption(j);
                                break;
                            }
                        }
                    }
                };

                scope.deleteAllOptions = function () {
                    scope.param.definition.options = [];
                };

                scope.removeOption = function (index) {
                    scope.param.definition.options.splice(index, 1);
                };

                scope.launchDefinitionDialog = function (param) {
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_definer_dialog.html",
                        scope: scope,
                        controller: TestWizardParamDefinerController,
                        resolve: {
                            param: function () {
                                return param;
                            },
                            typesCollection: function () {
                                return scope.typesCollection;
                            }
                        },
                        size: "prc-lg"
                    });

                    modalInstance.result.then(function (result) {
                    }, function () {
                    });
                };

                scope.addField = function () {
                    if (!("fields" in scope.param.definition))
                        scope.param.definition.fields = [];
                    scope.param.definition.fields.push({
                        type: 0,
                        name: "",
                        label: "",
                        definition: {placeholder: 0}
                    });
                };

                scope.removeAllFields = function () {
                    scope.param.definition.fields = [];
                };

                scope.removeSelectedFields = function () {
                    var selectedRows = scope.groupGridApi.selection.getSelectedRows();
                    var rows = scope.groupGridApi.grid.rows;
                    for (var i = 0; i < selectedRows.length; i++) {
                        for (var j = 0; j < rows.length; j++) {
                            if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
                                scope.removeField(j);
                                break;
                            }
                        }
                    }
                };

                scope.removeField = function (index) {
                    scope.param.definition.fields.splice(index, 1);
                };

                scope.$watch('param.type', function (newValue, oldValue) {
                    if (!scope.param)
                        return;
                    if (newValue === null || newValue === undefined)
                        return;

                    if (newValue != oldValue) {
                        if (newValue == 9) {
                            scope.param.definition = {fields: [], placeholder: 0};
                        } else if (newValue == 10) {
                            scope.param.definition = {
                                element: {
                                    type: 0,
                                    definition: {placeholder: 0}
                                }
                            };
                        } else if (scope.param.value == 4) {
                            scope.param.definition = {options: [], placeholder: 0};
                        } else {
                            scope.param.definition = {placeholder: 0};
                        }
                    }

                    element.html($templateCache.get("type_" + newValue + "_definer.html"));
                    $compile(element.contents())(scope);
                });

                scope.$watch('param.definition.element.type', function (newValue, oldValue) {
                    if (newValue === null || newValue === undefined)
                        return;
                    if (newValue != oldValue) {
                        if (scope.param.type == 10) {
                            scope.param.definition.element.definition = {placeholder: 0};
                        }
                    }
                });
            }
        };
    }]);