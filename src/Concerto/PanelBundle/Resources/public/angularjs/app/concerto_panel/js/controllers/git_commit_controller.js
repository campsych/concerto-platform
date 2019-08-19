function GitCommitController($scope, $uibModalInstance) {

    $scope.message = "";

    $scope.commit = function () {
        $uibModalInstance.close({
            message: $scope.message
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
}
