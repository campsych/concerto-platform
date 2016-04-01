function TextareaController($scope, $uibModalInstance, value, readonly, title, tooltip) {
    $scope.value = value;
    $scope.readonly = readonly;
    $scope.title = title;
    $scope.tooltip = tooltip;

    $scope.close = function () {
        $uibModalInstance.close($scope.value);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}