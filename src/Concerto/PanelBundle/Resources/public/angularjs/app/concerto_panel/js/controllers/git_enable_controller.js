function GitEnableController($scope, $uibModalInstance, exposedSettingsMap) {
    $scope.exposedSettingsMap = exposedSettingsMap;
    $scope.url = $scope.exposedSettingsMap.git_url;
    $scope.branch = $scope.exposedSettingsMap.git_branch;
    $scope.login = $scope.exposedSettingsMap.git_login;
    $scope.password = $scope.exposedSettingsMap.git_password;

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
