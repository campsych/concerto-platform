function DataTableImportCsvController($scope, $uibModalInstance, FileUploader, $http, $uibModal, DialogsService, object, editable) {
  $scope.importCsvPath = Paths.DATA_TABLE_IMPORT_CSV;

  $scope.object = object;
  $scope.editable = editable;
  $scope.item = null;
  $scope.restructure = false;
  $scope.headerRow = false;
  $scope.delimiter = ",";
  $scope.enclosure = '"';
  $scope.dialogsService = DialogsService;
  $scope.dirty = false;

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

  $scope.uploader.onCompleteItem = function (item, response, status, headers) {
    if (response.result === 0) {
      $scope.item = item;
    } else {
      $scope.showErrorAlert();
    }
  };


  $scope.save = function () {
    $http.post($scope.importCsvPath.pf($scope.object.id, $scope.restructure ? 1 : 0, $scope.headerRow ? 1 : 0, $scope.delimiter, $scope.enclosure), {
      file: $scope.item.file.name
    }).success(function (response) {
      if (response.result === 0) {
        $scope.dialogsService.alertDialog(
            Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT,
            Trans.DATA_TABLE_IO_DIALOG_MESSAGE_IMPORTED,
            "success"
        );
      } else {
        $scope.dialogsService.alertDialog(
            Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT,
            response.errors[0],
            "danger",
            "prc-lg"
        );
      }
      $uibModalInstance.close($scope.item.file.name);
    }).error(function (data, status, headers, config) {
      $scope.dirty = true;
      $scope.showErrorAlert();
    });
  };

  $scope.showErrorAlert = function () {
    $scope.dialogsService.alertDialog(
        Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT,
        Trans.DATA_TABLE_IO_DIALOG_MESSAGE_ERROR,
        "danger"
    );
    $scope.cancel();
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss($scope.dirty);
  };
}
