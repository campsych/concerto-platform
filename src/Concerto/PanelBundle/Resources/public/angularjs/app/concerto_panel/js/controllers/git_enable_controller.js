function GitEnableController($scope, $uibModalInstance) {

    $scope.url = "";
    $scope.branch = "master";
    $scope.login = "";
    $scope.password = "";
    $scope.import = true;

    $scope.enable = function () {
        $uibModalInstance.close({
            url: $scope.url,
            branch: $scope.branch,
            login: $scope.login,
            password: $scope.password,
            import: $scope.import
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
}
