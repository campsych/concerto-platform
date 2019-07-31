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

    $scope.importUrl = function () {
        var importSource = $scope.uploadItem !== null ?
            "Content will be imported from uploaded file in <strong>File</strong> field.<br/>" :
            "Content will be imported from <strong>URL</strong> field.<br/>";
        DialogsService.confirmDialog(
            "Importing content",
            importSource +
            "Are you sure you want to import content? This is operation can not be undone. Please make sure you have a backup of your data in case anything goes wrong.",
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_CONTENT_IMPORT, {
                    url: $scope.exposedSettingsMap.content_url,
                    instructions: $scope.exposedSettingsMap.content_url_import_options,
                    file: $scope.uploadItem ? $scope.uploadItem.file.name : null
                }).then(function (httpResponse) {
                    var success = httpResponse.data.result === 0;
                    var title = success ?
                        "Import finished successfully" :
                        "Import failed";

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