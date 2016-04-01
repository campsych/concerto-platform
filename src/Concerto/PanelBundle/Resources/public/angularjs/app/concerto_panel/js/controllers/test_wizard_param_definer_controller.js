function TestWizardParamDefinerController($scope, $uibModalInstance, param, typesCollection) {

    $scope.param = param;
    $scope.typesCollection = typesCollection;

    $scope.change = function () {
        $uibModalInstance.close($scope.param);
    };
}