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

    $scope.isGitEnabled = function () {
        return $scope.gitStatus !== null;
    };

    $scope.enableGit = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "git_enable_dialog.html",
            controller: GitEnableController,
            size: "lg",
            backdrop: 'static',
            keyboard: false
        });
        modalInstance.result.then(function (userResponse) {
            $http.post(Paths.ADMINISTRATION_GIT_ENABLE, userResponse).then(function (httpResponse) {
                $scope.refreshSettings();
                var success = httpResponse.data.result === 0;
                var title = success ? Trans.GIT_ENABLE_SUCCESS : Trans.GIT_ENABLE_FAILURE;
                DialogsService.preDialog(
                    title,
                    null,
                    httpResponse.data.output);
            });
        });
    };

    $scope.disableGit = function () {
        DialogsService.confirmDialog(
            Trans.GIT_DISABLE_TITLE,
            Trans.GIT_DISABLE_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_DISABLE, {}).then(function (httpResponse) {
                    $scope.gitStatus = null;
                    $scope.refreshSettings();
                });
            }
        );
    };

    $scope.showDiff = function (sha) {
        if (!$scope.canDiff(sha)) return;
        $http.get(Paths.ADMINISTRATION_GIT_DIFF.pf(sha)).then(function (httpResponse) {

            var success = httpResponse.data.result == 0;
            if (!success) {
                DialogsService.alertDialog(
                    Trans.GIT_DIFF_SHA.pf(sha),
                    httpResponse.data.errors.join("<br/>"),
                    "danger",
                    "lg"
                );
                return;
            }

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
                Trans.GIT_DIFF_SHA.pf(sha),
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
            Trans.GIT_DIFF_LOCAL,
            diffHtml,
            "none",
            "prc-lg"
        );
    };

    $scope.hasUncommittedChanges = function () {
        if (!$scope.isGitEnabled()) return false;
        return $scope.gitStatus.diff !== '';
    };

    $scope.refreshGitStatus = function () {
        $http.post(Paths.ADMINISTRATION_GIT_STATUS, {
            exportInstructions: $scope.exposedSettingsMap.content_export_options
        }).then(function (httpResponse) {

            var success = httpResponse.data.result == 0;
            if (!success) {
                DialogsService.alertDialog(
                    Trans.GIT_REFRESH_TITLE,
                    httpResponse.data.errors.join("<br/>"),
                    "danger",
                    "lg"
                );
                return;
            }

            $scope.gitStatus = httpResponse.data.status;
        });
    };

    $scope.canDiff = function (sha) {
        return $scope.gitStatus.history[$scope.gitStatus.history.length - 1].sha !== sha;
    };

    $scope.canCommit = function () {
        return $scope.hasUncommittedChanges();
    };

    $scope.commit = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "git_commit_dialog.html",
            controller: GitCommitController,
            size: "lg",
            backdrop: 'static',
            keyboard: false
        });
        modalInstance.result.then(function (userResponse) {
            $http.post(Paths.ADMINISTRATION_GIT_COMMIT, userResponse).then(function (httpResponse) {
                var success = httpResponse.data.result === 0;
                var title = success ? Trans.GIT_COMMIT_SUCCESS : Trans.GIT_COMMIT_FAILURE;
                var content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n");

                $scope.refreshGitStatus();
                DialogsService.preDialog(
                    title,
                    null,
                    content
                );
            });
        });
    };

    $scope.canReset = function () {
        return $scope.hasUncommittedChanges();
    };

    $scope.reset = function () {
        DialogsService.confirmDialog(
            Trans.GIT_RESET_TITLE,
            Trans.GIT_RESET_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_RESET, {
                    exportInstructions: $scope.exposedSettingsMap.content_export_options
                }).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    var success = httpResponse.data.result === 0;
                    var title = success ? Trans.GIT_RESET_SUCCESS : Trans.GIT_RESET_FAILURE;
                    var content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n");

                    DialogsService.preDialog(
                        title,
                        null,
                        content
                    );
                });
            }
        );
    };

    $scope.canPush = function () {
        return $scope.isGitEnabled() && $scope.gitStatus.ahead > 0 && $scope.gitStatus.behind == 0;
    };

    $scope.push = function () {
        DialogsService.confirmDialog(
            Trans.GIT_PUSH_TITLE,
            Trans.GIT_PUSH_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_PUSH, {}).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    var success = httpResponse.data.result === 0;
                    var title = success ? Trans.GIT_PUSH_SUCCESS : Trans.GIT_PUSH_FAILURE;
                    var content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n");

                    DialogsService.preDialog(
                        title,
                        null,
                        content
                    );
                });
            }
        );
    };

    $scope.canPull = function () {
        return $scope.isGitEnabled() && $scope.gitStatus.behind > 0;
    };

    $scope.pull = function () {
        DialogsService.confirmDialog(
            Trans.GIT_PULL_TITLE,
            Trans.GIT_PULL_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_PULL, {
                    exportInstructions: $scope.exposedSettingsMap.content_export_options
                }).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    var success = httpResponse.data.result === 0;
                    var title = success ? Trans.GIT_PULL_SUCCESS : Trans.GIT_PULL_FAILURE;
                    var content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n");

                    DialogsService.preDialog(
                        title,
                        null,
                        content
                    );
                });
            }
        );
    };

    $scope.$watch("exposedSettingsMap.git_enabled", function (newValue) {
        if (newValue == 1) $scope.refreshGitStatus();
    });
}

concertoPanel.controller('AdministrationContentController', ["$scope", "$http", "DialogsService", "$window", "FileUploader", "$uibModal", AdministrationContentController]);