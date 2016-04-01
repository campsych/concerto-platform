function PortValueEditController($scope, $uibModalInstance, $timeout, object) {
    $scope.object = object;

    $scope.change = function () {
        $scope.object.defaultValue = "0";
        $uibModalInstance.close($scope.object);
    };
    
    $scope.reset = function() {
        $scope.object.defaultValue = "1";
        $uibModalInstance.close($scope.object);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };

    $timeout(function () {
        $scope.codemirrorForceRefresh++;
    }, 20);
}