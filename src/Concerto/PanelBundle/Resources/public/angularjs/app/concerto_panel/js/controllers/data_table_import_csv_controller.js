function DataTableImportCsvController($scope, $uibModalInstance, FileUploader, $http, $uibModal, DialogsService, object, editable) {
    $scope.object = object;
    $scope.editable = editable;
    $scope.item = null;
    $scope.restructure = false;
    $scope.headerRow = false;
    $scope.delimiter = ",";
    $scope.enclosure = '"';
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
        if (response.result.success) {
            $scope.item = item;
        } else {
            $scope.showErrorAlert();
        }
    };


    $scope.save = function () {
        $http.post(Paths.DATA_TABLE_IMPORT_CSV.pf($scope.object.id, $scope.restructure ? 1 : 0, $scope.headerRow ? 1 : 0, $scope.delimiter, $scope.enclosure), {
            file: $scope.item.file.name,
            objectTimestamp: $scope.object.updatedOn
        }).then(
            function success(httpResponse) {
                if (httpResponse.data.result === 0) {
                    // no need to update timestamp as collection will be refreshed
                    DialogsService.alertDialog(
                        Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT,
                        Trans.DATA_TABLE_IO_DIALOG_MESSAGE_IMPORTED,
                        "success"
                    );
                } else {
                    DialogsService.alertDialog(
                        Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT,
                        httpResponse.data.errors.join("<br/>"),
                        "danger",
                        "prc-lg"
                    );
                }
                $uibModalInstance.close($scope.item.file.name);
            },
            function error(httpResponse) {
                $scope.dirty = true;
                $scope.showErrorAlert();
            }
        );
    };

    $scope.showErrorAlert = function () {
        DialogsService.alertDialog(
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
