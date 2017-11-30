function TestWizardController($scope, $uibModal, $http, $filter, $state, $sce, $timeout, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, TestWizardParam, AdministrationSettingsService) {
  $scope.tabStateName = "wizards";
  BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, TestWizardCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService);
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

  $scope.steps = [];
  $scope.stepsOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    data: 'steps',
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.stepsOptions.enableFiltering = !$scope.stepsOptions.enableFiltering;
          $scope.stepsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
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
        '<button type="button" class="btn btn-default btn-xs" ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" ng-click="grid.appScope.editStep(row.entity.id);">' + Trans.TEST_WIZARD_STEP_LIST_BUTTON_EDIT + '</button>' +
        '<button type="button" class="btn btn-danger btn-xs" ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" ng-click="grid.appScope.deleteStep(row.entity.id);">' + Trans.TEST_WIZARD_STEP_LIST_BUTTON_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ]
  };

  $scope.wizardParamsOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.wizardParamsOptions.enableFiltering = !$scope.wizardParamsOptions.enableFiltering;
          $scope.paramsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
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
        '<button type="button" class="btn btn-default btn-xs" ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" ng-click="grid.appScope.editParam(row.entity.id);">' + Trans.TEST_WIZARD_PARAM_LIST_BUTTON_EDIT + '</button>' +
        '<button type="button" class="btn btn-danger btn-xs" ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" ng-click="grid.appScope.deleteParam(row.entity.id);">' + Trans.TEST_WIZARD_PARAM_LIST_BUTTON_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ]
  };

  $scope.fetchStep = function (id, callback) {
    for (var i = 0; i < $scope.steps.length; i++) {
      var step = $scope.steps[i];
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
    $scope.dialogsService.confirmDialog(
        Trans.TEST_WIZARD_STEP_DIALOG_TITLE_CLEAR,
        Trans.TEST_WIZARD_STEP_DIALOG_MESSAGE_CLEAR_CONFIRM,
        function (response) {
          $http.post($scope.deleteAllStepsPath.pf($scope.object.id), {}).success(function (data) {
            $scope.setWorkingCopyObject();
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
          });
        }
    );
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

    $scope.dialogsService.confirmDialog(
        Trans.TEST_WIZARD_STEP_DIALOG_TITLE_DELETE,
        Trans.TEST_WIZARD_STEP_DIALOG_MESSAGE_DELETE_CONFIRM,
        function (response) {
          $http.post($scope.deleteStepPath.pf(ids), {}).success(function (data) {
            $scope.setWorkingCopyObject();
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
          });
        }
    );
  };
  $scope.deleteAllParams = function () {
    $scope.dialogsService.confirmDialog(
        Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_CLEAR,
        Trans.TEST_WIZARD_PARAM_DIALOG_MESSAGE_CLEAR_CONFIRM,
        function (response) {
          $http.post($scope.deleteAllParamsPath.pf($scope.object.id), {}).success(function (data) {
            $scope.setWorkingCopyObject();
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
          });
        }
    );
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

    $scope.dialogsService.confirmDialog(
        Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_DELETE,
        Trans.TEST_WIZARD_PARAM_DIALOG_MESSAGE_DELETE_CONFIRM,
        function (response) {
          $http.post($scope.deleteParamPath.pf(ids), {}).success(function (data) {
            $scope.setWorkingCopyObject();
            $scope.fetchObjectCollection();
            $scope.testCollectionService.fetchObjectCollection();
          });
        }
    );
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
      $scope.setWorkingCopyObject();
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
          return $scope.steps;
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
      $scope.setWorkingCopyObject();
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
    if ("steps" in $scope) {
      for (var i = 0; i < $scope.steps.length; i++) {
        var step = $scope.steps[i];
        for (var j = 0; j < step.params.length; j++) {
          var param = step.params[j];
          TestWizardParam.serializeParamValue(param);
        }
      }
    }
  };

  $scope.onAfterPersist = function () {
    $scope.testCollectionService.fetchObjectCollection();
  };

  $scope.onDelete = function () {
    $scope.testCollectionService.fetchObjectCollection();
  };

  $scope.getPersistObject = function () {
    var obj = angular.copy($scope.object);
    obj.serializedSteps = angular.toJson($scope.steps);
    delete obj.steps;
    return obj;
  };

  $scope.resetObject();
  $scope.initializeColumnDefs();

  $scope.$watchCollection("object.steps", function () {
    if ($scope.paramsGridApi)
      $scope.paramsGridApi.selection.clearSelectedRows();
    if ($scope.stepsGridApi)
      $scope.stepsGridApi.selection.clearSelectedRows();

    if ($scope.object.steps != null) {
      $scope.steps = $scope.object.steps;

      var params = [];
      for (var i = 0; i < $scope.object.steps.length; i++) {
        var step = $scope.object.steps[i];
        for (var j = 0; j < step.params.length; j++) {
          params.push(step.params[j]);
        }
      }
      $scope.params = params;
    } else
      $scope.params = [];
  });
}

concertoPanel.controller('TestWizardController', ["$scope", "$uibModal", "$http", "$filter", "$state", "$sce", "$timeout", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "TestWizardParam", "AdministrationSettingsService", TestWizardController]);
