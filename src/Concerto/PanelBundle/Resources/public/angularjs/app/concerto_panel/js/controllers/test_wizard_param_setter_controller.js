function TestWizardParamSetterController($scope, $uibModalInstance, param, output, parent, values, wizardObject, wizardMode) {

    $scope.param = param;
    $scope.output = output;
    $scope.parent = parent;
    $scope.values = values;
    $scope.wizardObject = wizardObject;
    $scope.wizardMode = wizardMode;

    $scope.change = function () {
        $uibModalInstance.close($scope.output);
    };
}