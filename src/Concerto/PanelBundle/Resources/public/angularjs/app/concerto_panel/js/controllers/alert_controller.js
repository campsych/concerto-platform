function AlertController($scope, $uibModalInstance, title, content, type) {
    $scope.title = title;
    $scope.content = content;
    $scope.type = type;

    $scope.ok = function() {
        $uibModalInstance.close(1);
    };
}