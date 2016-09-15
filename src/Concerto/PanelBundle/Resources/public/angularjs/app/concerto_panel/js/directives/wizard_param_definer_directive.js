angular.module('concertoPanel').directive('wizardParamDefiner', ["$compile", "$filter", "$templateCache", "$uibModal", "uiGridConstants", "TestWizardParam", "GridService", "RDocumentation", "DataTableCollectionService", "TestCollectionService", "ViewTemplateCollectionService", function ($compile, $filter, $templateCache, $uibModal, uiGridConstants, TestWizardParam, GridService, RDocumentation, DataTableCollectionService, TestCollectionService, ViewTemplateCollectionService) {
        return {
            restrict: 'E',
            scope: {
                param: "=",
                typesCollection: "=types"
            },
            link: function (scope, element, attrs, controllers) {
                scope.sortedTypesCollection = $filter('orderBy')(scope.typesCollection, "label");
                scope.dataTableCollectionService = DataTableCollectionService;
                scope.testCollectionService = TestCollectionService;
                scope.viewTemplateCollectionService = ViewTemplateCollectionService;
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

                scope.colMapOptions = {
                    enableFiltering: true,
                    enableGridMenu: true,
                    exporterMenuCsv: false,
                    exporterMenuPdf: false,
                    importerShowMenu: false,
                    data: 'param.definition.cols',
                    exporterCsvFilename: 'export.csv',
                    showGridFooter: true,
                    columnDefs: [
                        {
                            displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_NAME,
                            field: "name"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_LABEL,
                            field: "label"
                        }, {
                            displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_TOOLTIP,
                            field: "tooltip"
                        }, {
                            displayName: "",
                            name: "_action",
                            enableSorting: false,
                            enableFiltering: false,
                            enableCellEdit: false,
                            exporterSuppressExport: true,
                            cellTemplate:
                                    "<div class='ui-grid-cell-contents' align='center'>" +
                                    '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeColumn(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_COLUMN_MAP_LIST_BUTTON_DELETE + '</button>' +
                                    "</div>",
                            width: 100
                        }
                    ],
                    onRegisterApi: function (gridApi) {
                        scope.colMapGridApi = gridApi;
                    },
                    importerDataAddCallback: function (gridApi, newObjects) {
                        scope.param.definition.cols = scope.param.definition.cols.concat(newObjects);
                    },
                    enableCellEditOnFocus: true
                };

                scope.$watch("param.definition.cols.length", function (newValue) {
                    scope.colMapOptions.enableFiltering = newValue > 0;
                    if (scope.colMapGridApi && uiGridConstants.dataChange) {
                        scope.colMapGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                    }
                });

                scope.addColumn = function () {
                    if (!("cols" in scope.param.definition))
                        scope.param.definition.cols = [];
                    scope.param.definition.cols.push({
                        name: "",
                        label: "",
                        tooltip: ""
                    });
                };

                scope.deleteSelectedColumns = function () {
                    var selectedRows = scope.selectGridApi.selection.getSelectedRows();
                    var rows = scope.colMapGridApi.grid.rows;
                    for (var i = 0; i < selectedRows.length; i++) {
                        for (var j = 0; j < rows.length; j++) {
                            if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
                                scope.removeColumn(j);
                                break;
                            }
                        }
                    }
                };

                scope.deleteAllColumns = function () {
                    scope.param.definition.cols = [];
                };

                scope.removeColumn = function (index) {
                    scope.param.definition.cols.splice(index, 1);
                };

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
                            editDropdownOptionsArray: scope.sortedTypesCollection,
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
                        switch (parseInt(newValue)) {
                            case 0:
                            case 1:
                            case 2:
                            case 5:
                            case 6:
                            case 8:
                                scope.param.definition = {defvalue: ""};
                                break;
                            case 3:
                                scope.param.definition = {options: [], defvalue: ""};
                                break;
                            case 4:
                                scope.param.definition = {defvalue: "0"};
                                break;
                            case 9:
                                scope.param.definition = {fields: []};
                                break;
                            case 10:
                                scope.param.definition = {
                                    element: {
                                        type: 0,
                                        definition: {placeholder: 0}
                                    }
                                };
                                break;
                            case 12:
                                scope.param.definition = {cols: []};
                                break;
                            default:
                                scope.param.definition = {placeholder: 0};
                                break;
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