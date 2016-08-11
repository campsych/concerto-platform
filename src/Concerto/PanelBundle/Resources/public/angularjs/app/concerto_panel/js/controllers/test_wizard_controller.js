function TestWizardController($scope, $uibModal, $http, $filter, $state, $sce, $timeout, uiGridConstants, GridService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, TestWizardParam) {
    $scope.tabStateName = "wizards";
    $scope.tabIndex = 5;
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, TestWizardCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService);
    $scope.exportable = true;

    $scope.deletePath = Paths.TEST_WIZARD_DELETE;
    $scope.addFormPath = Paths.TEST_WIZARD_ADD_FORM;
    $scope.fetchObjectPath = Paths.TEST_WIZARD_FETCH_OBJECT;
    $scope.savePath = Paths.TEST_WIZARD_SAVE;
    $scope.importPath = Paths.TEST_WIZARD_IMPORT;
    $scope.preImportStatusPath = Paths.TEST_WIZARD_PRE_IMPORT_STATUS;
    $scope.saveNewPath = Paths.TEST_WIZARD_SAVE_NEW;
    $scope.exportPath = Paths.TEST_WIZARD_EXPORT;
    $scope.stepsCollectionPath = Paths.TEST_WIZARD_STEP_COLLECTION;
    $scope.deleteAllStepsPath = Paths.TEST_WIZARD_STEP_DELETE_ALL;
    $scope.deleteStepPath = Paths.TEST_WIZARD_STEP_DELETE;
    $scope.fetchStepObjectPath = Paths.TEST_WIZARD_STEP_FETCH_OBJECT;
    $scope.paramsCollectionPath = Paths.TEST_WIZARD_PARAM_COLLECTION;
    $scope.deleteAllParamsPath = Paths.TEST_WIZARD_PARAM_DELETE_ALL;
    $scope.deleteParamPath = Paths.TEST_WIZARD_PARAM_DELETE;
    $scope.fetchParamObjectPath = Paths.TEST_WIZARD_PARAM_FETCH_OBJECT;

    $scope.formTitleAddLabel = Trans.TEST_WIZARD_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.TEST_WIZARD_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;

    $scope.tabAccordion.preview = {
        open: true
    };

    $scope.additionalColumnsDef = [{
            displayName: Trans.TEST_WIZARD_LIST_FIELD_NAME,
            field: "name"
        }, {
            displayName: Trans.TEST_WIZARD_LIST_FIELD_TEST,
            field: "testName"
        }];

    $scope.testCollectionService = TestCollectionService;
    $scope.step = null;
    $scope.param = null;
    $scope.params = [];
    $scope.getTypeName = function (type) {
        return TestWizardParam.getTypeName(type);
    };

    $scope.stepsOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        data: 'object.steps',
        onRegisterApi: function (gridApi) {
            $scope.stepsGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.TEST_WIZARD_STEP_LIST_FIELD_ID,
                field: "id",
                type: "number",
                width: 75
            }, {
                enableSorting: false,
                exporterSuppressExport: true,
                displayName: Trans.TEST_WIZARD_STEP_LIST_FIELD_INFO,
                field: "description",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                        '<i style="vertical-align:middle;" class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                        "</div>",
                width: 50
            }, {
                displayName: Trans.TEST_WIZARD_STEP_LIST_FIELD_TITLE,
                field: "title"
            }, {
                displayName: Trans.TEST_WIZARD_STEP_LIST_FIELD_ORDER,
                type: "number",
                field: "orderNum"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                        "<div class='ui-grid-cell-contents' align='center'>" +
                        '<button type="button" class="btn btn-default btn-xs" ng-disabled="grid.appScope.object.initProtected == \'1\'" ng-click="grid.appScope.editStep(row.entity.id);">' + Trans.TEST_WIZARD_STEP_LIST_BUTTON_EDIT + '</button>' +
                        '<button type="button" class="btn btn-danger btn-xs" ng-disabled="grid.appScope.object.initProtected == \'1\'" ng-click="grid.appScope.deleteStep(row.entity.id);">' + Trans.TEST_WIZARD_STEP_LIST_BUTTON_DELETE + '</button>' +
                        "</div>",
                width: 100
            }
        ]
    };

    $scope.$watch("object.steps.length", function (newValue) {
        $scope.stepsOptions.enableFiltering = newValue > 0;
        if ($scope.stepsGridApi && uiGridConstants.dataChange) {
            $scope.stepsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.wizardParamsOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        onRegisterApi: function (gridApi) {
            $scope.paramsGridApi = gridApi;
        },
        data: 'params',
        columnDefs: [
            {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_ID,
                field: "id",
                type: "number",
                width: 75
            }, {
                enableSorting: false,
                exporterSuppressExport: true,
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_INFO,
                field: "description",
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                        '<i class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                        "</div>",
                width: 50
            }, {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_LABEL,
                field: "label"
            }, {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_STEP,
                field: "stepTitle"
            }, {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_VARIABLE,
                field: "name"
            }, {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_TYPE,
                field: "type",
                cellTemplate: "<div class='ui-grid-cell-contents'>{{grid.appScope.getTypeName(COL_FIELD)}}</div>"
            }, {
                displayName: Trans.TEST_WIZARD_PARAM_LIST_FIELD_ORDER,
                type: "number",
                field: "order"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                        "<div class='ui-grid-cell-contents' align='center'>" +
                        '<button type="button" class="btn btn-default btn-xs" ng-disabled="grid.appScope.object.initProtected == \'1\'" ng-click="grid.appScope.editParam(row.entity.id);">' + Trans.TEST_WIZARD_PARAM_LIST_BUTTON_EDIT + '</button>' +
                        '<button type="button" class="btn btn-danger btn-xs" ng-disabled="grid.appScope.object.initProtected == \'1\'" ng-click="grid.appScope.deleteParam(row.entity.id);">' + Trans.TEST_WIZARD_PARAM_LIST_BUTTON_DELETE + '</button>' +
                        "</div>",
                width: 100
            }
        ]
    };

    $scope.$watch("params.length", function (newValue) {
        $scope.wizardParamsOptions.enableFiltering = newValue > 0;
        if ($scope.paramsGridApi && uiGridConstants.dataChange) {
            $scope.paramsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });

    $scope.fetchStep = function (id, callback) {
        for (var i = 0; i < $scope.object.steps.length; i++) {
            var step = $scope.object.steps[i];
            if (step.id == id) {
                $scope.step = step;
                break;
            }
        }
        if (callback != null) {
            callback.call(this);
        }
    };

    $scope.fetchParam = function (id, callback) {
        for (var i = 0; i < $scope.params.length; i++) {
            var param = $scope.params[i];
            if (param.id == id) {
                $scope.param = param;
                break;
            }
        }
        if (callback != null) {
            callback.call(this);
        }
    };

    $scope.addStep = function () {
        $scope.step = {
            id: 0,
            title: "",
            description: "",
            orderNum: 0,
            wizard: $scope.object.id
        };
        $scope.launchStepDialog($scope.step);
    };

    $scope.editStep = function (id) {
        $scope.fetchStep(id, function () {
            $scope.launchStepDialog($scope.step);
        });
    };

    $scope.addParam = function () {
        $scope.param = {
            id: 0,
            label: "",
            description: "",
            passableThroughUrl: "0",
            hideCondition: "",
            order: 0,
            wizardStep: 0,
            testVariable: 0,
            type: 0,
            definition: {placeholder: 0},
            wizard: $scope.object.id
        };
        $scope.launchParamDialog($scope.param);
    };

    $scope.editParam = function (id) {
        $scope.param = $scope.collectionService.getParam(id);
        $scope.launchParamDialog($scope.param);
    };

    $scope.deleteAllSteps = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_WIZARD_STEP_DIALOG_TITLE_CLEAR;
                },
                content: function () {
                    return Trans.TEST_WIZARD_STEP_DIALOG_MESSAGE_CLEAR_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteAllStepsPath.pf($scope.object.id), {
            }).success(function (data) {
                $scope.fetchObjectCollection();
                $scope.testCollectionService.fetchObjectCollection();
            });
        }, function () {
        });
    };

    $scope.deleteSelectedSteps = function () {
        var ids = [];
        for (var i = 0; i < $scope.stepsGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.stepsGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.deleteStep(ids);
    };

    $scope.deleteStep = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_WIZARD_STEP_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.TEST_WIZARD_STEP_DIALOG_MESSAGE_DELETE_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteStepPath.pf(ids), {
            }).success(function (data) {
                $scope.fetchObjectCollection();
                $scope.testCollectionService.fetchObjectCollection();
            });
        }, function () {
        });
    };
    $scope.deleteAllParams = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_CLEAR;
                },
                content: function () {
                    return Trans.TEST_WIZARD_PARAM_DIALOG_MESSAGE_CLEAR_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteAllParamsPath.pf($scope.object.id), {
            }).success(function (data) {
                $scope.fetchObjectCollection();
                $scope.testCollectionService.fetchObjectCollection();
            });
        }, function () {
        });
    };

    $scope.deleteSelectedParams = function () {
        var ids = [];
        for (var i = 0; i < $scope.paramsGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.paramsGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.deleteParam(ids);
    };

    $scope.deleteParam = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.TEST_WIZARD_PARAM_DIALOG_MESSAGE_DELETE_CONFIRM;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.deleteParamPath.pf(ids), {
            }).success(function (data) {
                $scope.fetchObjectCollection();
                $scope.testCollectionService.fetchObjectCollection();
            });
        }, function () {
        });
    };

    $scope.launchStepDialog = function (step) {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "step_dialog.html",
            controller: TestWizardStepSaveController,
            scope: $scope,
            resolve: {
                object: function () {
                    return step;
                }
            },
            size: "lg"
        });

        modalInstance.result.then(function (result) {
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
        }, function () {
        });
    };

    $scope.launchParamDialog = function (param) {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_dialog.html",
            controller: TestWizardParamSaveController,
            scope: $scope,
            resolve: {
                TestWizardParam: function () {
                    return TestWizardParam;
                },
                wizardSteps: function () {
                    return $scope.object.steps;
                },
                wizardParams: function () {
                    return $scope.params;
                },
                test: function () {
                    return $scope.testCollectionService.get($scope.object.test);
                },
                object: function () {
                    return param;
                }
            },
            size: "prc-lg"
        });

        modalInstance.result.then(function (result) {
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
        }, function () {
        });
    };

    $scope.resetObject = function () {
        $scope.object = {
            id: 0,
            accessibility: 0,
            name: "",
            description: "",
            test: 0
        };
    };

    $scope.onBeforePersist = function () {
        if ("steps" in $scope.object) {
            for (var i = 0; i < $scope.object.steps.length; i++) {
                var step = $scope.object.steps[i];
                for (var j = 0; j < step.params.length; j++) {
                    var param = step.params[j];
                    TestWizardParam.serializeParamValue(param);
                }
            }
        }
    };

    $scope.onDelete = function () {
        $scope.testCollectionService.fetchObjectCollection();
    };

    $scope.onObjectChanged = function (newObject, oldObject) {
        $scope.super.onObjectChanged(newObject, oldObject);

        if ($scope.paramsGridApi)
            $scope.paramsGridApi.selection.clearSelectedRows();
        if ($scope.stepsGridApi)
            $scope.stepsGridApi.selection.clearSelectedRows();

        if (newObject.steps != null) {
            var params = [];
            for (var i = 0; i < newObject.steps.length; i++) {
                var step = newObject.steps[i];
                for (var j = 0; j < step.params.length; j++) {
                    params.push(step.params[j]);
                }
            }
            $scope.params = params;
        } else
            $scope.params = [];
    };

    $scope.getPersistObject = function () {
        var obj = angular.copy($scope.object);
        obj.serializedSteps = angular.toJson(obj.steps);
        delete obj.steps;
        delete obj.testObject;
        return obj;
    };

    $scope.resetObject();
    $scope.initializeColumnDefs();
    $scope.fetchObjectCollection();
}

concertoPanel.controller('TestWizardController', ["$scope", "$uibModal", "$http", "$filter", "$state", "$sce", "$timeout", "uiGridConstants", "GridService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "TestWizardParam", TestWizardController]);
