function ImportController($scope, $uibModalInstance, $http, $uibModal, FileUploader, importPath) {
    $scope.name = "";
    $scope.item = null;
    $scope.progress = 0;
    $scope.object.validationErrors = [];
    $scope.importPath = importPath;

    $scope.getFileName = function () {
        return $scope.item.file.name;
    };

    // no way to use this module without constructing a new instance, unfortunately
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

    $scope.persistImport = function () {
        $scope.object.validationErrors = [];

        var obj = {
            "name": $scope.name,
            "file": $scope.item.file.name
        };

        $http.post($scope.importPath, obj).then(
                function successCallback(response) {
                    switch (response.data.result) {
                        case BaseController.RESULT_OK:
                        {
                            $uibModal.open({
                                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                                controller: AlertController,
                                size: "sm",
                                resolve: {
                                    title: function () {
                                        return Trans.IMPORT_DIALOG_TITLE;
                                    },
                                    content: function () {
                                        return Trans.IMPORT_DIALOG_MESSAGE_IMPORTED;
                                    },
                                    type: function () {
                                        return "success";
                                    }
                                }
                            });

                            $uibModalInstance.close($scope.object);
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED:
                        {
                            $scope.object.validationErrors = response.data.errors;
                            break;
                        }
                    }
                },
                function errorCallback(response) {
                    $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                        controller: AlertController,
                        size: "sm",
                        resolve: {
                            title: function () {
                                return Trans.DIALOG_TITLE_SAVE;
                            },
                            content: function () {
                                return Trans.DIALOG_MESSAGE_FAILED;
                            },
                            type: function () {
                                return "danger";
                            }
                        }
                    });
                });
    };

    $scope.save = function () {
        $scope.persistImport();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}