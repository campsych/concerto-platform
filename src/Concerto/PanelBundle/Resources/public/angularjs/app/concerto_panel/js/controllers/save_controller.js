function SaveController($scope, $uibModalInstance) {

    $scope.save = function() {
        $scope.persist($uibModalInstance);
    };

    $scope.cancel = function() {
        $uibModalInstance.dismiss(0);
    };
}