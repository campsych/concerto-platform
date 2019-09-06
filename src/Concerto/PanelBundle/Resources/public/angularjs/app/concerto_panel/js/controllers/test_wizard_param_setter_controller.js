function TestWizardParamSetterController($scope, $uibModalInstance) {

    $scope.change = function () {
        $uibModalInstance.close($scope.output);
    };
}