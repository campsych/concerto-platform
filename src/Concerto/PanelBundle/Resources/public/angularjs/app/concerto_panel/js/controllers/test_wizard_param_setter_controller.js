function TestWizardParamSetterController($scope, $uibModalInstance, param, output, parent, values, wizardObject, wizardMode, editable) {

    $scope.param = param;
    $scope.output = output;
    $scope.parent = parent;
    $scope.values = values;
    $scope.wizardObject = wizardObject;
    $scope.wizardMode = wizardMode;
    $scope.editable = editable;

    $scope.change = function () {
        $uibModalInstance.close($scope.output);
    };
}