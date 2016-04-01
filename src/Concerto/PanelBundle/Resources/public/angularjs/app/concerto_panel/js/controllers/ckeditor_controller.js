function CKEditorController($scope, $uibModalInstance, title, tooltip, value) {
    
    $scope.title = title;
    $scope.tooltip = tooltip;
    $scope.value = value;
    $scope.editorOptions = Defaults.ckeditorTestContentOptions;

    $scope.change = function() {
        $uibModalInstance.close($scope.value);
    };

    $scope.cancel = function() {
        $uibModalInstance.dismiss(0);
    };
}