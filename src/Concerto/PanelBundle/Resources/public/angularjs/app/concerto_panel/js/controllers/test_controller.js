function TestController($scope, $uibModal, $http, $filter, $timeout, $state, $sce, uiGridConstants, GridService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, TestWizardParam, RDocumentation) {
    $scope.tabStateName = "tests";
    $scope.tabIndex = 0;
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, TestCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService);
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
    $scope.deleteVariablePath = Paths.TEST_VARIABLE_DELETE;

    $scope.formTitleAddLabel = Trans.TEST_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.TEST_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;
    
    $scope.copiedNodes = [];

    $scope.getWizardCellTemplate = function (col) {
        if (col !== null) {
            return "<a href='#/wizards/" + col.id + "'><i class='glyphicon glyphicon-link'></i>" + col.name + "</a>";
        } else {
            return Trans.NONE;
        }
    };
    $scope.getSourceTestCellTemplate = function (col, entity) {
        if (col !== null) {
            var cell = "<a href='#/tests/" + col.test + "'>";
            //if (entity.outdated === "1") {
            //    cell += "<i class='glyphicon glyphicon-warning-sign red' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_LIST_TIP_OUTDATED + "\"'></i>";
            //} else {
            //    cell += "<i class='glyphicon glyphicon-ok-circle green' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_LIST_TIP_UPTODATE + "\"'></i>";
            //}
            cell += "<i class='glyphicon glyphicon-link'></i>" + col.testName + "</a>";
            return cell;
        } else {
            return Trans.NONE;
        }
    };
    $scope.additionalColumnsDef = [{
            displayName: Trans.TEST_LIST_FIELD_NAME,
            field: "name",
        }, {
            name: "wizard",
            displayName: Trans.TEST_LIST_FIELD_WIZARD,
            field: "sourceWizardObject",
            cellTemplate: "<div class='ui-grid-cell-contents' bind-html-compile='grid.appScope.getWizardCellTemplate(COL_FIELD, row.entity)'></div>"
        }, {
            name: "wizard_source",
            displayName: Trans.TEST_LIST_FIELD_WIZARD_SOURCE,
            field: "sourceWizardObject",
            cellTemplate: "<div class='ui-grid-cell-contents' bind-html-compile='grid.appScope.getSourceTestCellTemplate(COL_FIELD, row.entity)'></div>"
        }];

    $scope.collectionOptions.exporterFieldCallback = function (grid, row, col, input) {
        switch (col.name) {
            case "wizard":
            {
                if (!input)
                    return "";
                else
                    return input.name;
                break;
            }
            case "wizard_source":
            {
                if (!input)
                    return "";
                else
                    return input.testObject.name;
                break;
            }
            default:
                return input;
                break;
        }
    };

    $scope.additionalListButtons = [
        '<button ng-show="row.entity.visibility!=2" class="btn btn-primary btn-xs" ng-click="grid.appScope.startTest(row.entity.slug);">' + Trans.TEST_BUTTON_RUN + '</button>'
    ];

    $scope.rCacheDirectory = Paths.R_CACHE_DIRECTORY;
    $scope.rDocumentationHtml = false;

    $scope.testWizardCollectionService = TestWizardCollectionService;

    $scope.params = [];
    $scope.returns = [];
    $scope.branches = [];

    $scope.varsSectionCollapsed = true;

    $scope.tabAccordion.logic = {
        open: true
    };

    // Each mapping (incl default one) must have a controller and matching dialog template
    $scope.autocompletionWizardMapping = {
        'concerto.table.query': {
            template: 'concerto_table_query_wizard_dialog.html',
            controller: ConcertoTableQueryWizardController
        },
        '#default': {
            template: 'default_r_completion_wizard_dialog.html',
            controller: DefaultRCompletionWizardController
        }
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
            resumable: 0,
            validationErrors: [],
            logs: [],
            variables: [],
            nodes: [],
            nodesConnections: []
        };
    };

    $scope.logsOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "object.logs",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        onRegisterApi: function (gridApi) {
            $scope.logsGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.TEST_LOG_LIST_FIELD_DATE,
                field: "created"
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
                        '<i class="glyphicon glyphicon-align-justify clickable" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true" ng-click="grid.appScope.showSingleTextareaModal(COL_FIELD, true, \'' + Trans.TEST_LOG_LIST_FIELD_MESSAGE + '\',\'' + Trans.TEST_LOG_LIST_FIELD_MESSAGE + '\')"></i>' +
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

    $scope.$watch("object.logs.length", function (newValue) {
        $scope.logsOptions.enableFiltering = newValue > 0;
        if ($scope.logsGridApi && uiGridConstants.dataChange) {
            $scope.logsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.paramsOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "params",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
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
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_PARAMS_LIST_EDIT + '</button>' +
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_PARAMS_LIST_DELETE + '</button>' +
                        '</div>',
                width: 100
            }
        ]
    };

    $scope.$watch("params.length", function (newValue) {
        $scope.paramsOptions.enableFiltering = newValue > 0;
        if ($scope.paramsGridApi && uiGridConstants.dataChange) {
            $scope.paramsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.returnsOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "returns",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
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
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_RETURNS_LIST_EDIT + '</button>' +
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_RETURNS_LIST_DELETE + '</button>' +
                        "</div>",
                width: 100
            }
        ]
    };

    $scope.$watch("returns.length", function (newValue) {
        $scope.returnsOptions.enableFiltering = newValue > 0;
        if ($scope.returnsGridApi && uiGridConstants.dataChange) {
            $scope.returnsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.branchesOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "branches",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
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
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-default btn-xs" ng-click="grid.appScope.editVariable(row.entity.id);">' + Trans.TEST_VARS_BRANCHES_LIST_EDIT + '</button>' +
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteVariable(row.entity.type, row.entity.id);">' + Trans.TEST_VARS_BRANCHES_LIST_DELETE + '</button>' +
                        "</div>",
                width: 100
            }
        ]
    };

    $scope.$watch("branches.length", function (newValue) {
        $scope.branchesOptions.enableFiltering = newValue > 0;
        if ($scope.branchesGridApi && uiGridConstants.dataChange) {
            $scope.branchesGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.showDocumentationHelp = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "r_documentation_generation_help.html",
            controller: RDocumentationGenerationHelpController,
            scope: $scope,
            resolve: {
            },
            size: "lg"
        });
    };

    $scope.launchWizard = function (widget, replacement, completion, data) {
        var funct_name = RDocumentation.sanitizeFunctionName(replacement);
        var handler = ($scope.autocompletionWizardMapping[ funct_name ]) ? $scope.autocompletionWizardMapping[ funct_name ] :
                $scope.autocompletionWizardMapping[ '#default' ];

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + handler.template,
            controller: handler.controller, //             
//             
//             templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "default_r_completion_wizard_dialog.html",
//             controller: DefaultRCompletionWizardController,
            scope: $scope,
            resolve: {
                completionWidget: function () {
                    return widget;
                },
                selection: function () {
                    return replacement;
                },
                completionContext: function () {
                    return completion;
                },
                completionData: function () {
                    return data;
                }
            },
            size: "prc-lg"
        });
    };



//     RDocumentation.setup( $http, $sce, $scope );
    $scope.rDocumentationActive = false;
    $scope.codeOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'r',
        viewportMargin: Infinity,
        hintOptions: {
            completeSingle: false,
            selectCallback: function (selected) {
                $scope.rDocumentationActive = true;

                RDocumentation.select(selected, function (value) {
                    $scope.rDocumentationHtml = value;
                }
                );
            },
            wizardCallback: $scope.launchWizard,
            closeCallback: function () {
                $scope.rDocumentationActive = false;
                $scope.rDocumentationHtml = false;
                $scope.$apply();
            },
            functionIndex: []
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

    $http.get($scope.rCacheDirectory + 'functionIndex.json').success(function (data) {
        if (data !== null) {
            $scope.codeOptions.hintOptions.functionIndex = data;
        }
    });


    $scope.fetchVariable = function (id, callback) {
        $http.get($scope.fetchVariableObjectPath.pf(id)).success(function (object) {
            if (object !== null) {
                $scope.variable = object;
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
                    //jsPlumb.repaintEverything();
                }, 20);
            }
    );

    $scope.onObjectChanged = function (newObject, oldObject) {
        $scope.super.onObjectChanged(newObject, oldObject);

        if ($scope.logsGridApi)
            $scope.logsGridApi.selection.clearSelectedRows();
        if ($scope.paramsGridApi)
            $scope.paramsGridApi.selection.clearSelectedRows();
        if ($scope.returnsGridApi)
            $scope.returnsGridApi.selection.clearSelectedRows();
        if ($scope.branchesGridApi)
            $scope.branchesGridApi.selection.clearSelectedRows();

        if (newObject.sourceWizardObject != null) {
            newObject.steps = newObject.sourceWizardObject.steps;
            TestWizardParam.testVariablesToWizardParams($scope.object.variables, newObject.sourceWizardObject.steps);
        }

        if (newObject.variables != null) {
            var params = [];
            var returns = [];
            var branches = [];
            for (var i = 0; i < newObject.variables.length; i++) {
                var variable = newObject.variables[i];
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
    };

    $scope.onBeforePersist = function () {
        if ($scope.object.type !== 1) {
            $scope.object.sourceWizard = null;
            $scope.object.sourceWizardObject = null;
        }
        if ($scope.object.sourceWizardObject != null) {
            TestWizardParam.wizardParamsToTestVariables($scope.object, $scope.object.steps, $scope.object.variables);
        }
    }

    $scope.deleteAllLogs = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_LOG_DIALOG_TITLE_CLEAR;
                },
                content: function () {
                    return Trans.TEST_LOG_DIALOG_MESSAGE_CLEAR_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteAllLogsPath.pf($scope.object.id), {
            }).success(function (data) {
                $scope.refreshLogs();
            });
        }, function () {
        });
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

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_LOG_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.TEST_LOG_DIALOG_MESSAGE_DELETE_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteLogPath.pf(ids), {
            }).success(function (data) {
                $scope.refreshLogs();
            });
        }, function () {
        });
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

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return confirmationTitle;
                },
                content: function () {
                    return confirmationMessage;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteVariablePath.pf(ids), {
            }).success(function (data) {
                $scope.collectionService.fetchObjectCollection();
                $scope.testWizardCollectionService.fetchObjectCollection();
            });
        }, function () {
        });
    };

    $scope.updateDependent = function () {
        $http.post(Paths.TEST_UPDATE.pf($scope.object.id), {
        }).success(function (data) {
            switch (data.result) {
                case BaseController.RESULT_OK:
                {
                    //TODO
                    break;
                }
                case BaseController.RESULT_VALIDATION_FAILED:
                {
                    //TODO
                    break;
                }
            }
            $scope.fetchObjectCollection();
            var modalInstance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                controller: AlertController,
                size: "sm",
                resolve: {
                    title: function () {
                        return Trans.TEST_DIALOG_TITLE_UDPATE;
                    },
                    content: function () {
                        return Trans.TEST_DIALOG_MESSAGE_UDPATE_SUCCESSFUL;
                    },
                    type: function () {
                        return "success";
                    }
                }
            });
        });
    };

    $scope.convertToR = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_LOGIC_CONVERT_TITLE;
                },
                content: function () {
                    return Trans.TEST_LOGIC_CONVERT_CONFIRMATION;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $scope.object.type = 0;
            $scope.object.sourceWizard = null;
            $scope.object.sourceWizardObject = null;
        }, function () {
        });
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
                }
            },
            size: "lg"
        });

        modalInstance.result.then(function (result) {
            $scope.collectionService.fetchObjectCollection();
            $scope.testWizardCollectionService.fetchObjectCollection();
        }, function () {
        });
    };

    $scope.refreshLogs = function () {
        $scope.collectionService.fetchLogsCollection($scope.object.id);
    };

    $scope.refreshVariables = function () {
        $scope.collectionService.fetchVariablesCollection($scope.object.id);
    };

    $scope.onDelete = function () {
        TestWizardCollectionService.fetchObjectCollection();
    };

    $scope.startTest = function (slug) {
        if (!slug)
            slug = $scope.object.slug;
        window.open(Paths.TEST_RUN.pf(slug), '_blank');
    };

    $scope.debugTest = function () {
        window.open(Paths.TEST_DEBUG.pf($scope.object.slug), '_blank');
    };

    $scope.getPersistObject = function () {
        var obj = angular.copy($scope.object);
        delete obj.logs;
        delete obj.nodes;
        delete obj.nodesConnections;
        delete obj.sourceWizardObject;
        delete obj.steps;
        obj.serializedVariables = angular.toJson(obj.variables);
        delete obj.variables;
        return obj;
    };

    $scope.resetObject();
    $scope.initializeColumnDefs();
    $scope.fetchObjectCollection();
}

concertoPanel.controller('TestController', ["$scope", "$uibModal", "$http", "$filter", "$timeout", "$state", "$sce", "uiGridConstants", "GridService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "TestWizardParam", "RDocumentation", TestController]);
