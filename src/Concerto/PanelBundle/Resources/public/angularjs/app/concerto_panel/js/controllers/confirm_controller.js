function ConfirmController($scope, $uibModalInstance, title, content) {
    $scope.title = title;
    $scope.content = content;

    $scope.ok = function() {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function() {
        $uibModalInstance.dismiss(0);
    };
}