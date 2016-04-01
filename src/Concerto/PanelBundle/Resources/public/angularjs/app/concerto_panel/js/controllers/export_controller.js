function ExportController($scope, $uibModalInstance, title, content, ids) {
    $scope.title = title;
    $scope.content = content;
    $scope.type = 'info';

    $scope.exportFormat = 'compressed';

    $scope.export = function () {
        $uibModalInstance.close($scope.exportFormat);
    };
    
    $scope.cancel = function() {
        $uibModalInstance.dismiss(0);
    };
}