function TestController($scope, $uibModal, $http, $filter, $timeout, $state, $sce, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, TestWizardParam, RDocumentation, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService) {
    $scope.tabStateName = "tests";
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, TestCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService);
    $scope.exportable = true;

    $scope.deletePath = Paths.TEST_DELETE;
    $scope.addFormPath = Paths.TEST_ADD_FORM;
    $scope.fetchObjectPath = Paths.TEST_FETCH_OBJECT;
    $scope.fetchVariableObjectPath = Paths.TEST_VARIABLE_FETCH_OBJECT;
    $scope.savePath = Paths.TEST_SAVE;
    $scope.importPath = Paths.TEST_IMPORT;
    $scope.preImportStatusPath = Paths.TEST_PRE_IMPORT_STATUS;
    $scope.saveNewPath = Paths.TEST_SAVE_NEW;
    $scope.exportPath = Paths.TEST_EXPORT;
    $scope.logsCollectionPath = Paths.TEST_LOG_COLLECTION;
    $scope.deleteAllLogsPath = Paths.TEST_LOG_DELETE_ALL;
    $scope.deleteLogPath = Paths.TEST_LOG_DELETE;
    $scope.paramsCollectionPath = Paths.TEST_PARAMS_COLLECTION;
    $scope.returnsCollectionPath = Paths.TEST_RETURNS_COLLECTION;
    $scope.branchesCollectionPath = Paths.TEST_BRANCHES_COLLECTION;
    $scope.exportInstructionsPath = Paths.TEST_EXPORT_INSTRUCTIONS;
    $scope.lockPath = Paths.TEST_LOCK;

    $scope.formTitleAddLabel = Trans.TEST_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.TEST_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;

    $scope.RDocumentation = RDocumentation;
    $scope.copiedNodes = [];

    $scope.setWorkingCopyObject = function () {
        $scope.workingCopyObject = {
            id: $scope.object.id,
            name: $scope.object.name,
            archived: $scope.object.archived,
            accessibility: $scope.object.accessibility,
            owner: $scope.object.owner,
            groups: $scope.object.groups,
            slug: $scope.object.slug,
            visibility: $scope.object.visibility,
            type: $scope.object.type,
            code: $scope.object.code
        };
    };

    $scope.getWizardCellTemplate = function (col, entity) {
        if (entity.sourceWizard !== null) {
            return "<a href='#/wizards/" + entity.sourceWizard + "'><i class='glyphicon glyphicon-link'></i>" + entity.sourceWizardName + "</a>";
        } else {
            return "-";
        }
    };
    $scope.getSourceTestCellTemplate = function (col, entity) {
        if (entity.sourceWizard !== null) {
            var cell = "<a href='#/tests/" + entity.sourceWizardTest + "'>";
            cell += "<i class='glyphicon glyphicon-link'></i>" + entity.sourceWizardTestName + "</a>";
            return cell;
        } else {
            return "-";
        }
    };
    $scope.additionalColumnsDef = [
        {
            displayName: Trans.TEST_LIST_FIELD_NAME,
            field: "name"
        }, {
            displayName: Trans.TEST_LIST_FIELD_SLUG,
            field: "slug"
        }, {
            name: "wizard",
            displayName: Trans.TEST_LIST_FIELD_WIZARD,
            cellTemplate: "<div class='ui-grid-cell-contents' ng-html='grid.appScope.getWizardCellTemplate(COL_FIELD, row.entity)'></div>"
        }, {
            name: "wizard_source",
            displayName: Trans.TEST_LIST_FIELD_WIZARD_SOURCE,
            cellTemplate: "<div class='ui-grid-cell-contents' ng-html='grid.appScope.getSourceTestCellTemplate(COL_FIELD, row.entity)'></div>"
        }
    ];

    $scope.collectionOptions.exporterFieldCallback = function (grid, row, col, input) {
        switch (col.name) {
            case "wizard": {
                if (!input)
                    return "";
                else
                    return input.name;
                break;
            }
            case "wizard_source": {
                if (!input)
                    return "";
                else
                    return input.testName;
                break;
            }
            default:
                return input;
                break;
        }
    };

    $scope.additionalListButtons = [
        '<button ng-show="row.entity.visibility!=2" class="btn btn-primary btn-xs" ng-click="grid.appScope.startTest(row.entity.slug, row.entity.protected);">' + Trans.TEST_BUTTON_RUN + '</button>'
    ];
    $scope.testWizardCollectionService = TestWizardCollectionService;

    $scope.params = [];
    $scope.returns = [];
    $scope.branches = [];
    $scope.logs = [];

    $scope.varsSectionCollapsed = true;

    $scope.tabAccordion.logic = {
        open: true
    };

    $scope.visibilities = [
        {value: 0, label: Trans.TEST_FORM_FIELD_VISIBILITY_REGULAR},
        {value: 1, label: Trans.TEST_FORM_FIELD_VISIBILITY_FEATURED},
        {value: 2, label: Trans.TEST_FORM_FIELD_VISIBILITY_SUBTEST}
    ];

    $scope.types = [
        {value: 2, label: Trans.TEST_FORM_FIELD_TYPE_FLOW},
        {value: 0, label: Trans.TEST_FORM_FIELD_TYPE_CODE},
        {value: 1, label: Trans.TEST_FORM_FIELD_TYPE_WIZARD}
    ];

    $scope.resetObject = function () {
        $scope.object = {
            id: 0,
            accessibility: 0,
            name: "",
            code: "",
            description: "",
            visibility: 0,
            type: 2,
            validationErrors: [],
            logs: [],
            variables: [],
            nodes: [],
            nodesConnections: [],
            steps: []
        };
    };

    $scope.logsOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "logs",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.logsOptions.enableFiltering = !$scope.logsOptions.enableFiltering;
                    $scope.logsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.logsGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.TEST_LOG_LIST_FIELD_DATE,
                field: "created",
                sort: {direction: 'desc', priority: 0}
            }, {
                displayName: Trans.TEST_LOG_LIST_FIELD_BROWSER,
                field: "browser"
            }, {
                displayName: Trans.TEST_LOG_LIST_FIELD_IP,
                field: "ip"
            }, {
                displayName: Trans.TEST_LOG_LIST_FIELD_MESSAGE,
                field: "message",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify clickable" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true" ng-click="grid.appScope.dialogsService.textareaDialog(\'' + Trans.TEST_LOG_LIST_FIELD_MESSAGE + '\', COL_FIELD, \'' + Trans.TEST_LOG_LIST_FIELD_MESSAGE + '\', true)"></i>' +
                    "</div>"
            }, {
                displayName: Trans.TEST_LOG_LIST_FIELD_TYPE,
                field: "type",
                cellTemplate: '<div class="ui-grid-cell-contents">{{COL_FIELD==0?"' + Trans.TEST_LOG_LIST_FIELD_TYPE_JAVASCRIPT + '":"' + Trans.TEST_LOG_LIST_FIELD_TYPE_R + '"}}</div>'
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate: '<div class="ui-grid-cell-contents" align="center"><button type="button" class="btn btn-danger btn-xs" ng-click="deleteLog(row.entity.id);">' + Trans.TEST_LOG_LIST_BUTTON_DELETE + '</button></div>',
                width: 60
            }
        ]
    };

    $scope.paramsOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "params",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.paramsOptions.enableFiltering = !$scope.paramsOptions.enableFiltering;
                    $scope.paramsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.paramsGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.TEST_VARS_PARAMS_LIST_FIELD_INFO,
                field: "description",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>",
                width: 50
            }, {
                displayName: Trans.TEST_VARS_PARAMS_LIST_FIELD_NAME,
                field: "name"
            }, {
                displayName: Trans.TEST_VARS_PARAMS_LIST_FIELD_URL,
                cellFilter: "logical",
                field: "passableThroughUrl"
            }, {
                displayName: Trans.TEST_VARS_PARAMS_LIST_FIELD_VALUE,
                field: "value",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                    "<div class='ui-grid-cell-contents' align='center'>" +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_PARAMS_LIST_EDIT + '</button>' +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_PARAMS_LIST_DELETE + '</button>' +
                    '</div>',
                width: 100
            }
        ]
    };

    $scope.returnsOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "returns",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.returnsOptions.enableFiltering = !$scope.returnsOptions.enableFiltering;
                    $scope.returnsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.returnsGridApi = gridApi;
        },
        columnDefs: [
            {
                enableSorting: false,
                exporterSuppressExport: true,
                displayName: Trans.TEST_VARS_RETURNS_LIST_FIELD_INFO,
                field: "description",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>",
                width: 50
            }, {
                displayName: Trans.TEST_VARS_RETURNS_LIST_FIELD_NAME,
                field: "name"
            }, {
                displayName: Trans.TEST_VARS_RETURNS_LIST_FIELD_VALUE,
                field: "value",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                    "<div class='ui-grid-cell-contents' align='center'>" +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_RETURNS_LIST_EDIT + '</button>' +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_RETURNS_LIST_DELETE + '</button>' +
                    "</div>",
                width: 100
            }
        ]
    };

    $scope.branchesOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "branches",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.branchesOptions.enableFiltering = !$scope.branchesOptions.enableFiltering;
                    $scope.branchesGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.branchesGridApi = gridApi;
        },
        columnDefs: [
            {
                enableSorting: false,
                exporterSuppressExport: true,
                displayName: Trans.TEST_VARS_BRANCHES_LIST_FIELD_INFO,
                field: "description",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>",
                width: 50
            }, {
                displayName: Trans.TEST_VARS_BRANCHES_LIST_FIELD_NAME,
                field: "name"
            }, {
                displayName: Trans.TEST_VARS_BRANCHES_LIST_FIELD_VALUE,
                field: "value",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                    "<div class='ui-grid-cell-contents' align='center'>" +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_BRANCHES_LIST_EDIT + '</button>' +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_BRANCHES_LIST_DELETE + '</button>' +
                    "</div>",
                width: 100
            }
        ]
    };

    $scope.codeOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'r',
        viewportMargin: Infinity,
        readOnly: !$scope.isEditable(),
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
        $http.get(RDocumentation.rCacheDirectory + 'functionIndex.json').then(httpResponse => {
            if (httpResponse.data !== null) {
                RDocumentation.functionIndex = httpResponse.data;
                $scope.codeOptions.hintOptions.functionIndex = httpResponse.data;
            }
        }, httpFailureResponse => {
        });
    } else {
        $scope.codeOptions.hintOptions.functionIndex = RDocumentation.functionIndex;
    }

    $scope.fetchVariable = function (id, callback) {
        $http.get($scope.fetchVariableObjectPath.pf(id)).then(function (httpResponse) {
            if (httpResponse.data !== null) {
                $scope.variable = httpResponse.data;
                if (callback != null) {
                    callback.call(this);
                }
            }
        });
    };


    // A hack to delay codemirror refresh, this variable should be changed shortly after changing scope contents
    // to make sure that codemirror properly refreshes its view
    $scope.codemirrorForceRefresh = 1;
    $scope.$watchCollection(
        "[ tabAccordion.logic.open, object.id, tabSection ]",
        function () {
            $timeout(function () {
                $scope.codemirrorForceRefresh++;
            }, 20);
        }
    );

    $scope.onObjectChanged = function () {
        $scope.super.onObjectChanged();

        if ($scope.logsGridApi)
            $scope.logsGridApi.selection.clearSelectedRows();

        $scope.codeOptions.readOnly = !$scope.isEditable();

        if($scope.object.id) {
            $scope.refreshLogs();
        }
    };

    $scope.onBeforePersist = function () {
        if ($scope.object.type !== 1) {
            $scope.object.sourceWizard = null;
        }
        if ($scope.object.sourceWizard != null) {
            TestWizardParam.wizardParamsToTestVariables($scope.object, $scope.object.steps, $scope.object.variables);
        }
    };

    $scope.deleteAllLogs = function () {
        $scope.dialogsService.confirmDialog(
            Trans.TEST_LOG_DIALOG_TITLE_CLEAR,
            Trans.TEST_LOG_DIALOG_MESSAGE_CLEAR_CONFIRM,
            function (response) {
                $http.post($scope.deleteAllLogsPath.pf($scope.object.id), {}).then(function (httpResponse) {
                    $scope.refreshLogs();
                });
            }
        );
    };

    $scope.deleteSelectedLogs = function () {
        var ids = [];
        for (var i = 0; i < $scope.logsGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.logsGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.deleteLog(ids);
    };

    $scope.deleteLog = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        $scope.dialogsService.confirmDialog(
            Trans.TEST_LOG_DIALOG_TITLE_DELETE,
            Trans.TEST_LOG_DIALOG_MESSAGE_DELETE_CONFIRM,
            function (response) {
                $http.post($scope.deleteLogPath.pf(ids), {}).then(function (httpResponse) {
                    $scope.refreshLogs();
                });
            }
        );
    };

    $scope.deleteSelectedVariables = function (type) {
        var ids = [];
        var collection = [];
        switch (type) {
            case 0:
                collection = $scope.paramsGridApi.selection.getSelectedRows();
                break;
            case 1:
                collection = $scope.returnsGridApi.selection.getSelectedRows();
                break;
            case 2:
                collection = $scope.branchesGridApi.selection.getSelectedRows();
                break;
        }
        for (var i = 0; i < collection.length; i++) {
            ids.push(collection[i].id);
        }
        $scope.deleteVariable(type, ids);
    };

    $scope.deleteVariable = function (type, ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        var confirmationMessage = "";
        var confirmationTitle = "";
        switch (type) {
            case 0:
                confirmationMessage = Trans.TEST_VARS_PARAMS_DIALOG_MESSAGE_DELETE_CONFIRM;
                confirmationTitle = Trans.TEST_VARS_PARAMS_DIALOG_TITLE_DELETE;
                break;
            case 1:
                confirmationMessage = Trans.TEST_VARS_RETURNS_DIALOG_MESSAGE_DELETE_CONFIRM;
                confirmationTitle = Trans.TEST_VARS_RETURNS_DIALOG_TITLE_DELETE;
                break;
            case 2:
                confirmationMessage = Trans.TEST_VARS_BRANCHES_DIALOG_MESSAGE_DELETE_CONFIRM;
                confirmationTitle = Trans.TEST_VARS_BRANCHES_DIALOG_TITLE_DELETE;
                break;
        }

        $scope.dialogsService.confirmDialog(
            confirmationTitle,
            confirmationMessage,
            function (response) {
                $http.post(Paths.TEST_VARIABLE_DELETE.pf(ids), {
                    objectTimestamp: $scope.object.updatedOn
                }).then(function (httpResponse) {
                    switch (httpResponse.data.result) {
                        case BaseController.RESULT_OK: {
                            $scope.setWorkingCopyObject();
                            $scope.fetchAllCollections();
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED: {
                            DialogsService.alertDialog(
                                Trans.DIALOG_TITLE_DELETE,
                                httpResponse.data.errors.join("<br/>"),
                                "danger"
                            );
                            break;
                        }
                    }
                });
            }
        );
    };

    $scope.convertToR = function () {
        $scope.dialogsService.confirmDialog(
            Trans.TEST_LOGIC_CONVERT_TITLE,
            Trans.TEST_LOGIC_CONVERT_CONFIRMATION,
            function (response) {
                $scope.object.type = 0;
                $scope.object.sourceWizard = null;
            }
        );
    };

    $scope.addVariable = function (type) {
        $scope.variable = {
            id: 0,
            name: "",
            description: "",
            type: type,
            test: $scope.object.id,
            passableThroughUrl: "0"
        };
        $scope.launchVariableDialog($scope.variable);
    };

    $scope.editVariable = function (id) {
        $scope.fetchVariable(id, function () {
            $scope.launchVariableDialog($scope.variable);
        });
    };

    $scope.launchVariableDialog = function (variable) {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "variables_dialog.html",
            controller: TestVariablesSaveController,
            scope: $scope,
            resolve: {
                object: function () {
                    return variable;
                },
                test: function () {
                    return $scope.object;
                }
            },
            size: "lg"
        });

        modalInstance.result.then(function (result) {
            $scope.setWorkingCopyObject();
            $scope.fetchAllCollections();
        });
    };

    $scope.refreshLogs = function () {
        $scope.collectionService.fetchLogsCollection($scope.object.id).then(logs => $scope.logs = logs);
    };

    $scope.refreshVariables = function () {
        $scope.collectionService.fetchVariablesCollection($scope.object.id);
    };

    $scope.isDelayedEditPossible = function () {
        return $scope.collectionService.collectionInitialized;
    };

    $scope.onDelete = function () {
    };

    $scope.startTest = function (slug, admin = null) {
        if (admin === null) admin = $scope.object.protected;
        if (!slug)
            slug = $scope.object.slug;
        let url = admin == 1 ? Paths.TEST_RUN_ADMIN : Paths.TEST_RUN;
        window.open(url.pf(slug), '_blank');
    };

    $scope.debugTest = function () {
        window.open(Paths.TEST_DEBUG.pf($scope.object.slug), '_blank');
    };

    $scope.getPersistObject = function () {
        let obj = angular.copy($scope.object);
        obj.objectTimestamp = $scope.object.updatedOn;
        delete obj.logs;
        delete obj.nodes;
        delete obj.nodesConnections;
        delete obj.steps;
        obj.serializedVariables = angular.toJson(obj.variables);
        delete obj.variables;
        return obj;
    };

    $scope.onAfterPersist = function () {
    };

    $scope.resetObject();
    $scope.initializeColumnDefs();

    $scope.$watchCollection("object.logs", function () {
        if ($scope.logsGridApi)
            $scope.logsGridApi.selection.clearSelectedRows();
    });

    $scope.$watchCollection("object.variables", function () {
        if ($scope.paramsGridApi)
            $scope.paramsGridApi.selection.clearSelectedRows();
        if ($scope.returnsGridApi)
            $scope.returnsGridApi.selection.clearSelectedRows();
        if ($scope.branchesGridApi)
            $scope.branchesGridApi.selection.clearSelectedRows();

        if ($scope.object.sourceWizard != null) {
            TestWizardParam.testVariablesToWizardParams($scope.object.variables, $scope.object.steps);
        }

        if ($scope.object.variables != null) {
            var params = [];
            var returns = [];
            var branches = [];
            for (var i = 0; i < $scope.object.variables.length; i++) {
                var variable = $scope.object.variables[i];
                switch (variable.type) {
                    case 0:
                        params.push(variable);
                        break;
                    case 1:
                        returns.push(variable);
                        break;
                    case 2:
                        branches.push(variable);
                        break;
                }
            }
            $scope.params = params;
            $scope.returns = returns;
            $scope.branches = branches;
        } else {
            $scope.params = [];
            $scope.returns = [];
            $scope.branches = [];
        }
    });
}

concertoPanel.controller('TestController', ["$scope", "$uibModal", "$http", "$filter", "$timeout", "$state", "$sce", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "TestWizardParam", "RDocumentation", "AdministrationSettingsService", "AuthService", "ScheduledTasksCollectionService", TestController]);
