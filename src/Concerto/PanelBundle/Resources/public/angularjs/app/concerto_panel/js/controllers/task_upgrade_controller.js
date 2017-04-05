function TaskUpgradeController($scope, $uibModalInstance, changelog) {
    $scope.changelog = changelog;
    $scope.backup = true;

    $scope.ok = function () {
        $uibModalInstance.close($scope.backup);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}