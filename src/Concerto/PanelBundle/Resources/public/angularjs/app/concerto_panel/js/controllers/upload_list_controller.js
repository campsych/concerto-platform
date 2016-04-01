function UploadListController($scope, $uibModalInstance, FileUploader) {
    $scope.item = null;
    $scope.progress = 0;

    $scope.getFileName = function () {
        return $scope.item.file.name;
    };

    $scope.uploader = new FileUploader({
        autoUpload: true,
        url: Paths.FILE_UPLOAD

    });

    $scope.uploader.onProgress = function (progress) {
        $scope.progress = progress;
    };

    $scope.uploader.onCompleteItem = function (item, response, status, headers) {
        if (response.result === 0) {
            $scope.item = item;
        }
    };

    $scope.upload = function () {
        $uibModalInstance.close($scope.item);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}