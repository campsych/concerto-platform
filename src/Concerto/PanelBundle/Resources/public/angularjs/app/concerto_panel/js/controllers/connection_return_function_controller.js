function ConnectionReturnFunctionController($scope, $uibModalInstance, $timeout, object, title) {
    $scope.object = object;
    $scope.title = title;

    $scope.change = function () {
        $scope.object.defaultReturnFunction = "0";
        $uibModalInstance.close($scope.object);
    };
    
    $scope.reset = function() {
        $scope.object.defaultReturnFunction = "1";
        $uibModalInstance.close($scope.object);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
    
    $timeout(function () {
        $scope.codemirrorForceRefresh++;
    }, 20);
}