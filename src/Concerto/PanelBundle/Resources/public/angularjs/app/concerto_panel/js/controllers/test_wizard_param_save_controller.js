function TestWizardParamSaveController($scope, $uibModalInstance, $http, $uibModal, TestWizardParam, wizardSteps, wizardParams, test, object) {
  $scope.savePath = Paths.TEST_WIZARD_PARAM_SAVE;
  $scope.testVariablesCollectionPath = Paths.TEST_PARAMS_COLLECTION;
  $scope.stepsCollectionPath = Paths.TEST_WIZARD_STEP_COLLECTION;
  $scope.typeCollectionPath = Paths.TEST_WIZARD_PARAM_TYPE_COLLECTION;

  $scope.test = test;
  $scope.wizardParams = wizardParams;
  $scope.testParams = [];
  $scope.wizardSteps = wizardSteps;
  $scope.testWizardParamService = TestWizardParam;
  $scope.object = object;
  $scope.dialogTitle = "";
  $scope.dialogSuccessfulMessage = "";

  //ids must be the same as indices
  $scope.types = [
    {id: 0, label: TestWizardParam.getTypeName(0), definer: true},
    {id: 1, label: TestWizardParam.getTypeName(1), definer: true},
    {id: 2, label: TestWizardParam.getTypeName(2), definer: true},
    {id: 3, label: TestWizardParam.getTypeName(3), definer: true},
    {id: 4, label: TestWizardParam.getTypeName(4), definer: true},
    {id: 5, label: TestWizardParam.getTypeName(5), definer: true},
    {id: 6, label: TestWizardParam.getTypeName(6), definer: true},
    {id: 7, label: TestWizardParam.getTypeName(7), definer: false},
    {id: 8, label: TestWizardParam.getTypeName(8), definer: true},
    {id: 9, label: TestWizardParam.getTypeName(9), definer: true},
    {id: 10, label: TestWizardParam.getTypeName(10), definer: true},
    {id: 11, label: TestWizardParam.getTypeName(11), definer: true},
    {id: 12, label: TestWizardParam.getTypeName(12), definer: true},
    {id: 13, label: TestWizardParam.getTypeName(13), definer: true}
  ];
  $scope.editorOptions = Defaults.ckeditorPanelContentOptions;

  $scope.fetchTestParamsCollection = function () {
    var result = [];
    for (var i = 0; i < $scope.test.variables.length; i++) {
      var variable = $scope.test.variables[i];
      if (variable.type === 0)
        result.push(variable);
    }
    $scope.testParams = result;
    return result;
  };

  $scope.save = function () {
    $scope.persist();
  };

  $scope.getPersistObject = function () {
    var obj = angular.copy($scope.object);
    TestWizardParam.serializeParamValue(obj);
    obj.serializedDefinition = angular.toJson(obj.definition);
    delete obj.definition;
    delete obj.output;
    return obj;
  };

  $scope.persist = function () {
    $scope.object.validationErrors = [];

    var oid = $scope.object.id;

    var addModalDialog = $uibModalInstance;
    $http.post($scope.savePath.pf(oid), $scope.getPersistObject()).success(function (data) {
      switch (data.result) {
        case BaseController.RESULT_OK: {
          if (addModalDialog != null) {
            addModalDialog.close($scope.object);
          }
          break;
        }
        case BaseController.RESULT_VALIDATION_FAILED: {
          $scope.object.validationErrors = data.errors;
          $(".modal").animate({scrollTop: 0}, "slow");
          break;
        }
      }
    });
  };

  $scope.launchDefinitionDialog = function (param) {
    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_definer_dialog.html",
      scope: $scope,
      controller: TestWizardParamDefinerController,
      resolve: {
        param: function () {
          return param;
        },
        typesCollection: function () {
          return $scope.types;
        }
      },
      size: "prc-lg"
    });

    modalInstance.result.then(function (result) {
    }, function () {
    });
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss(0);
  };

  $scope.$watch('object.type', function (newValue, oldValue) {
    if (!$scope.object)
      return;
    if (newValue === null || newValue === undefined)
      return;

    //output
    if (newValue != oldValue) {
      if (newValue == 7 || newValue == 9 || newValue == 12) {
        $scope.object.output = {};
      } else if (newValue == 10) {
        $scope.object.output = [];
      } else {
        $scope.object.output = null;
      }

      //definition
      if (newValue == 9) {
        $scope.object.definition = {fields: []};
      } else if (newValue == 10) {
        $scope.object.definition = {
          element: {
            type: 0,
            definition: {placeholder: 0}
          }
        };
      } else {
        $scope.object.definition = {placeholder: 0};
      }
    }
  });

  $scope.$watch('object.definition.element.type', function (newValue, oldValue) {
    if (newValue === null || newValue === undefined)
      return;
    if (newValue != oldValue) {
      if ($scope.object.type == 10) {
        $scope.object.output = [];
      }
    }
  });

  if ($scope.object.id === 0) {
    $scope.dialogTitle = Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_ADD;
  } else {
    $scope.dialogTitle = Trans.TEST_WIZARD_PARAM_DIALOG_TITLE_EDIT;
  }

  $scope.fetchTestParamsCollection();
}