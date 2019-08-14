function AdministrationContentController($scope, $http, DialogsService, $window, FileUploader, $uibModal) {
    $scope.gitStatus = null;
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
    };

    $scope.enableGit = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "enable_git_dialog.html",
            controller: GitEnableController,
            size: "lg",
            backdrop: 'static',
            keyboard: false
        });
        modalInstance.result.then(function (userResponse) {
            $http.post(Paths.ADMINISTRATION_GIT_ENABLE, userResponse).then(function (httpResponse) {
                $scope.refreshSettings();
                var success = httpResponse.data.result === 0;
                DialogsService.preDialog(
                    success ? "Success" : "Failure", //@TODO translation,
                    null,
                    httpResponse.data.output);
            });
        });
    };

    $scope.disableGit = function () {
        DialogsService.confirmDialog(
            "Disabling Git",
            "Are you sure you want to disable Git integration?",
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_DISABLE, {}).then(function (httpResponse) {
                    $scope.refreshSettings();
                });
            }
        );
    };

    $scope.showDiff = function (sha) {
        $http.get(Paths.ADMINISTRATION_GIT_DIFF.pf(sha)).then(function (httpResponse) {
            var diffHtml = Diff2Html.getPrettyHtml(
                httpResponse.data.diff,
                {
                    inputFormat: 'diff',
                    showFiles: false,
                    matching: 'lines',
                    outputFormat: 'side-by-side',
                    renderNothingWhenEmpty: true
                }
            );
            DialogsService.alertDialog(
                sha + " diff",
                diffHtml,
                "none",
                "prc-lg"
            );
        });
    };

    $scope.showLocalDiff = function () {
        var diffHtml = Diff2Html.getPrettyHtml(
            $scope.gitStatus.diff,
            {
                inputFormat: 'diff',
                showFiles: false,
                matching: 'lines',
                outputFormat: 'side-by-side',
                renderNothingWhenEmpty: true
            }
        );
        DialogsService.alertDialog(
            "Local diff",
            diffHtml,
            "none",
            "prc-lg"
        );
    };

    $scope.hasUncommittedChanges = function () {
        if ($scope.gitStatus === null) return false;
        return $scope.gitStatus.diff !== '';
    };

    $scope.refreshGitStatus = function () {
        $http.post(Paths.ADMINISTRATION_GIT_STATUS, {
            exportInstructions: $scope.exposedSettingsMap.content_export_options
        }).then(function (httpResponse) {
            $scope.gitStatus = httpResponse.data.status;
        });
    };

    $scope.refreshGitStatus();
}

concertoPanel.controller('AdministrationContentController', ["$scope", "$http", "DialogsService", "$window", "FileUploader", "$uibModal", AdministrationContentController]);