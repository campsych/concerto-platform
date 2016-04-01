function DownloadListController($scope, $uibModalInstance) {

    $scope.format = "csv";
    $scope.cols = "all";
    $scope.rows = "all";

    $scope.download = function () {
        $uibModalInstance.close({
            format: $scope.format,
            cols: $scope.cols,
            rows: $scope.rows
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}