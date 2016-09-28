function UploadListController($scope, $uibModalInstance, $uibModal, FileUploader) {
    $scope.item = null;

    $scope.getFileName = function () {
        return $scope.item.file.name;
    };

    $scope.uploader = new FileUploader({
        autoUpload: true,
        url: Paths.FILE_UPLOAD

    });
    
    $scope.showErrorAlert = function () {
        $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
            controller: AlertController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE;
                },
                content: function () {
                    return Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE;
                },
                type: function () {
                    return "danger";
                }
            }
        });
        $uibModalInstance.dismiss(0);
    };

    $scope.uploader.onCompleteItem = function (item, response, status, headers) {
        if (response.result === 0) {
            $scope.item = item;
        } else {
            $scope.showErrorAlert();
        }
    };

    $scope.upload = function () {
        $uibModalInstance.close($scope.item);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}