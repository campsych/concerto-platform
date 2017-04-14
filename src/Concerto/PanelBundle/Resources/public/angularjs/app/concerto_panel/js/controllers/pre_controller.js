function PreController($scope, $uibModalInstance, content, title, tooltip) {
    $scope.content = content;
    $scope.title = title;
    $scope.tooltip = tooltip;

    $scope.close = function () {
        $uibModalInstance.dismiss(0);
    };
}