function GitEnableController($scope, $uibModalInstance) {

    $scope.url = "";
    $scope.branch = "master";
    $scope.login = "";
    $scope.password = "";

    $scope.enable = function () {
        $uibModalInstance.close({
            url: $scope.url,
            branch: $scope.branch,
            login: $scope.login,
            password: $scope.password
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
}
