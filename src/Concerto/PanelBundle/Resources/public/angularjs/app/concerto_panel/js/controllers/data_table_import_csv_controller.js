function DataTableImportCsvController($scope, $uibModalInstance, FileUploader, $http, $uibModal, object) {
    $scope.importCsvPath = Paths.DATA_TABLE_IMPORT_CSV;

    $scope.object = object;
    $scope.item = null;
    $scope.progress = 0;
    $scope.restructure = false;
    $scope.headerRow = false;
    $scope.delimiter = ",";
    $scope.enclosure = '"';

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


    $scope.save = function () {
        $http.post($scope.importCsvPath.pf($scope.object.id, $scope.restructure ? 1 : 0, $scope.headerRow ? 1 : 0, $scope.delimiter, $scope.enclosure), {
            file: $scope.item.file.name
        }).success(function (response) {
            $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                controller: AlertController,
                size: "sm",
                resolve: {
                    title: function () {
                        return Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT;
                    },
                    content: function () {
                        return Trans.DATA_TABLE_IO_DIALOG_MESSAGE_IMPORTED;
                    },
                    type: function () {
                        return "success";
                    }
                }
            });
            $uibModalInstance.close($scope.item.file.name);
        }).error(function (data, status, headers, config) {
            $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                controller: AlertController,
                size: "sm",
                resolve: {
                    title: function () {
                        return Trans.DATA_TABLE_IO_DIALOG_TITLE_IMPORT;
                    },
                    content: function () {
                        return Trans.DATA_TABLE_IO_DIALOG_MESSAGE_ERROR;
                    },
                    type: function () {
                        return "danger";
                    }
                }
            });
            $uibModalInstance.close($scope.item.file.name);
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}
