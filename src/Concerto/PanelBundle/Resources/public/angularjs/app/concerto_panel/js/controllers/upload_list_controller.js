function UploadListController($scope, $uibModalInstance, $uibModal, FileUploader, DialogsService) {
  $scope.item = null;
  $scope.dialogsService = DialogsService;

  $scope.getFileName = function () {
    return $scope.item.file.name;
  };

  $scope.uploader = new FileUploader({
    autoUpload: true,
    url: Paths.FILE_UPLOAD,
    formData: [{
      dir: 0 //private
    }]
  });

  $scope.showErrorAlert = function () {
    $scope.dialogsService.alertDialog(
        Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE,
        Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE,
        "danger"
    );
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