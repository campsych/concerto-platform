function AdministrationContentController($scope, $http, DialogsService, $window, FileUploader, $uibModal, ScheduledTasksCollectionService) {
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
        $window.open(Paths.ADMINISTRATION_CONTENT_EXPORT.pf($scope.exposedSettingsMap.content_transfer_options), "_blank");
    };

    $scope.importUrl = function () {
        let importSource = $scope.uploadItem !== null ? Trans.CONTENT_IMPORT_FROM_FILE : Trans.CONTENT_IMPORT_FROM_URL;
        DialogsService.confirmDialog(
            Trans.CONTENT_IMPORTING_CONTENT,
            "<strong>" + importSource + "</strong><br/><br/>" +
            Trans.CONTENT_IMPORT_PROMPT,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_TASKS_CONTENT_IMPORT, {
                    url: $scope.exposedSettingsMap.content_url,
                    instructions: $scope.exposedSettingsMap.content_transfer_options,
                    file: $scope.uploadItem ? $scope.uploadItem.file.name : null
                }).then(function (httpResponse) {
                    let success = httpResponse.data.result === 0;

                    if (success) {
                        ScheduledTasksCollectionService.fetchObjectCollection(function () {
                            if (ScheduledTasksCollectionService.ongoingScheduledTasks.length > 0) {
                                ScheduledTasksCollectionService.launchOngoingTaskDialog();
                            }
                        });
                    } else {
                        DialogsService.alertDialog(
                            Trans.CONTENT_IMPORT_FAILURE,
                            httpResponse.data.errors.join("<br/>"),
                            "danger",
                            "lg"
                        );
                    }
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
        return $scope.exposedSettingsMap.git_enabled == 1;
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

            let success = httpResponse.data.result == 0;
            if (!success) {
                DialogsService.alertDialog(
                    Trans.GIT_DIFF_SHA.pf(sha),
                    httpResponse.data.errors.join("<br/>"),
                    "danger",
                    "lg"
                );
                return;
            }

            let diffHtml = Diff2Html.getPrettyHtml(
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
        let diffHtml = Diff2Html.getPrettyHtml(
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
        return $scope.gitStatus !== null && $scope.gitStatus.diff !== '';
    };

    $scope.refreshGitStatus = function () {
        $scope.refreshSettings();

        $http.post(Paths.ADMINISTRATION_GIT_STATUS, {}).then(function (httpResponse) {

            let success = httpResponse.data.result == 0;
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
        return $scope.hasUncommittedChanges() && !$scope.isOngoingGitTask();
    };

    $scope.commit = function () {
        let modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "git_commit_dialog.html",
            controller: GitCommitController,
            size: "lg"
        });
        modalInstance.result.then(function (userResponse) {
            $http.post(Paths.ADMINISTRATION_GIT_COMMIT, userResponse).then(function (httpResponse) {
                let success = httpResponse.data.result === 0;
                let title = success ? Trans.GIT_COMMIT_SUCCESS : Trans.GIT_COMMIT_FAILURE;
                let content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;

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
        return $scope.hasUncommittedChanges() && !$scope.isOngoingGitTask();
    };

    $scope.canPush = function () {
        return $scope.isGitEnabled() && $scope.gitStatus !== null && $scope.gitStatus.ahead > 0 && $scope.gitStatus.behind == 0 && !$scope.isOngoingGitTask();
    };

    $scope.push = function () {
        DialogsService.confirmDialog(
            Trans.GIT_PUSH_TITLE,
            Trans.GIT_PUSH_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_GIT_PUSH, {}).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    let success = httpResponse.data.result === 0;

                    if (success) {
                        ScheduledTasksCollectionService.fetchObjectCollection(function () {
                            if (ScheduledTasksCollectionService.ongoingScheduledTasks.length > 0) {
                                ScheduledTasksCollectionService.launchOngoingTaskDialog();
                            }
                        });
                    } else {
                        let content = success ? httpResponse.data.output : httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;
                        DialogsService.preDialog(
                            Trans.GIT_PUSH_FAILURE,
                            null,
                            content
                        );
                    }
                });
            }
        );
    };

    $scope.canPull = function () {
        return $scope.isGitEnabled() && $scope.gitStatus !== null && $scope.gitStatus.behind > 0 && !$scope.isOngoingGitTask();
    };

    $scope.pull = function () {
        DialogsService.confirmDialog(
            Trans.GIT_PULL_TITLE,
            Trans.GIT_PULL_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_TASKS_GIT_PULL, {
                    exportInstructions: $scope.exposedSettingsMap.content_transfer_options
                }).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    let success = httpResponse.data.result === 0;
                    if (success) {
                        ScheduledTasksCollectionService.fetchObjectCollection(function () {
                            if (ScheduledTasksCollectionService.ongoingScheduledTasks.length > 0) {
                                ScheduledTasksCollectionService.launchOngoingTaskDialog();
                            }
                        });
                    } else {
                        let content = httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;

                        DialogsService.preDialog(
                            Trans.GIT_PULL_TITLE,
                            null,
                            content
                        );
                    }
                });
            }
        );
    };

    $scope.enableGit = function () {
        let modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "git_enable_dialog.html",
            controller: GitEnableController,
            size: "lg",
            resolve: {
                exposedSettingsMap: function () {
                    return $scope.exposedSettingsMap;
                }
            }
        });

        modalInstance.result.then(function (userResponse) {
            userResponse.instructions = $scope.exposedSettingsMap.content_transfer_options;
            $http.post(Paths.ADMINISTRATION_TASKS_GIT_ENABLE, userResponse).then(function (httpResponse) {
                $scope.refreshGitStatus();
                let success = httpResponse.data.result === 0;
                if (!success) {
                    let content = httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;

                    DialogsService.preDialog(
                        Trans.GIT_ENABLE_TITLE,
                        null,
                        content
                    );
                }
            });
        });
    };

    $scope.update = function () {
        $http.post(Paths.ADMINISTRATION_TASKS_GIT_UPDATE, {
            exportInstructions: $scope.exposedSettingsMap.content_transfer_options
        }).then(function (httpResponse) {
            $scope.refreshGitStatus();

            let success = httpResponse.data.result === 0;
            if (!success) {
                let content = httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;

                DialogsService.preDialog(
                    Trans.GIT_UPDATE_TITLE,
                    null,
                    content
                );
            }
        });
    };

    $scope.reset = function () {
        DialogsService.confirmDialog(
            Trans.GIT_RESET_TITLE,
            Trans.GIT_RESET_CONFIRM,
            function (confirmResponse) {
                $http.post(Paths.ADMINISTRATION_TASKS_GIT_RESET, {
                    exportInstructions: $scope.exposedSettingsMap.content_transfer_options
                }).then(function (httpResponse) {
                    $scope.refreshGitStatus();

                    let success = httpResponse.data.result === 0;
                    if (!success) {
                        let content = httpResponse.data.errors.join("\n") + "\n\n" + httpResponse.data.output;

                        DialogsService.preDialog(
                            Trans.GIT_RESET_TITLE,
                            null,
                            content
                        );
                    }
                });
            }
        );
    };

    $scope.showLatestTaskOutput = function () {
        DialogsService.preDialog(
            $scope.gitStatus.latestTask.description,
            null,
            $scope.gitStatus.latestTask.output
        );
    };

    $scope.isOngoingGitTask = function () {
        return $scope.gitStatus !== null && $scope.gitStatus.latestTask !== null && $scope.gitStatus.latestTask.status < 2;
    };

    $scope.canImport = function () {
        return !$scope.isOngoingGitTask();
    };

    $scope.$watch("exposedSettingsMap.git_enabled", function (newValue) {
        if (newValue == 1) $scope.refreshGitStatus();
    });

    $scope.canUpdate = function () {
        return !$scope.isOngoingGitTask();
    };

    $scope.canEnableGit = function () {
        return $scope.exposedSettingsMap.git_enabled_overridable === "true" && !$scope.isOngoingGitTask();
    };

    $scope.canDisableGit = function () {
        return $scope.exposedSettingsMap.git_enabled_overridable === "true" && !$scope.isOngoingGitTask();
    };
}

concertoPanel.controller('AdministrationContentController', ["$scope", "$http", "DialogsService", "$window", "FileUploader", "$uibModal", "ScheduledTasksCollectionService", AdministrationContentController]);