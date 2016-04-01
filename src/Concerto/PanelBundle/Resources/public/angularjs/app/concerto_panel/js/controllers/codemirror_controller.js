function CodemirrorController($scope, $uibModalInstance, $timeout, value, title, tooltip) {
    $scope.value = value;
    $scope.title = title;
    $scope.tooltip = tooltip;

    $scope.close = function () {
        $uibModalInstance.close($scope.value);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
    
    $timeout(function () {
        $scope.codemirrorForceRefresh++;
    }, 20);
}