function AdministrationContentController($scope, $http, DialogsService, $window, FileUploader) {
    $scope.uploadItem = null;
    $scope.uploader = new FileUploader({
        autoUpload: true,
        url: Paths.FILE_UPLOAD,
        formData: [{
            dir: 0 //private
        }]
    });

    $scope.uploader.onCompleteItem = function (item, response, status, headers) {
        if (response.result.success) {
            $scope.uploadItem = item;
        } else {
            $scope.showErrorAlert();
        }
    };

    $scope.exportUrl = function () {
        $window.open(Paths.ADMINISTRATION_CONTENT_EXPORT.pf($scope.exposedSettingsMap.content_export_options), "_blank");
    };

    $scope.importUrl = function () {
        var importSource = $scope.uploadItem !== null ? Trans.CONTENT_IMPORT_FROM_FILE : Trans.CONTENT_IMPORT_FROM_URL;
        DialogsService.confirmDialog(
            Trans.CONTENT_IMPORTING_CONTENT,
            "<strong>" + importSource + "</strong><br/><br/>" +
            Trans.CONTENT_IMPORT_PROMPT,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_CONTENT_IMPORT, {
                    url: $scope.exposedSettingsMap.content_url,
                    instructions: $scope.exposedSettingsMap.content_import_options,
                    file: $scope.uploadItem ? $scope.uploadItem.file.name : null
                }).then(function (httpResponse) {
                    var success = httpResponse.data.result === 0;
                    var title = success ? Trans.CONTENT_IMPORT_SUCCESS : Trans.CONTENT_IMPORT_FAILURE;

                    DialogsService.preDialog(
                        title,
                        null,
                        httpResponse.data.output,
                        function (preResponse) {
                            $window.onbeforeunload = null;
                            $window.location.reload();
                        })
                });
            }
        );
    };

    $scope.showErrorAlert = function (content) {
        if (content == null) {
            content = Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE;
        }

        DialogsService.alertDialog(
            Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE,
            content,
            "danger"
        );
    };

    $scope.resetFile = function () {
        $scope.uploadItem = null;
        angular.element("#form-file-content-import-url")[0].reset();
    }
}

concertoPanel.controller('AdministrationContentController', ["$scope", "$http", "DialogsService", "$window", "FileUploader", AdministrationContentController]);