function RPackageInstallController($scope, $uibModalInstance) {
    $scope.method = 0;
    $scope.name = "";
    $scope.mirror = "https://www.stats.bris.ac.uk/R/";
    $scope.url = "";
    $scope.methodsCollection = [
        {value: 0, label: Trans.PACKAGES_DIALOG_FIELDS_METHOD_LATEST},
        {value: 1, label: Trans.PACKAGES_DIALOG_FIELDS_METHOD_SPECIFIC}
    ];

    $scope.install = function () {
        $uibModalInstance.close({
            method: $scope.method,
            name: $scope.name,
            mirror: $scope.mirror,
            url: $scope.url
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}