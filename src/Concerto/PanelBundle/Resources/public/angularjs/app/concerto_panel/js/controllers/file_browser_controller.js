function FileBrowserController($scope, $uibModal, $window, $timeout, FileUploader, $http) {
    $scope.tabStateName = "files";
    $scope.tabIndex = 3;

    $scope.delete_url = Paths.FILE_UPLOAD_DELETE;
    $scope.list_url = Paths.FILE_UPLOAD_LIST;
    $scope.upload_url = Paths.FILE_UPLOAD;

    /**
     * Saves the selected file into CKEditor.
     * 
     * This function is only used (and usable) when the file browser is open from within the CKEditor.
     */
    $scope.submitResult = function (cke_callback, url)
    {
        $window.opener.CKEDITOR.tools.callFunction(cke_callback, url);
        $window.close();
    };

    $scope.showErrorAlert = function () {
        $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
            controller: AlertController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE;
                },
                content: function () {
                    return Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE;
                },
                type: function () {
                    return "danger";
                }
            }
        });
    };

    $scope.remove = function (file) {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.DIALOG_MESSAGE_CONFIRM_DELETE;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post($scope.delete_url + file.name, {}).success(function (data) {
                if (data.result == 0)
                {
                    $scope.loadFiles();
                }
            });
        }, function () {
        });
    };

    $scope.loadFiles = function () {
        var stamp = new Date().getTime();
        var httpRequest = $http({
            method: 'GET',
            url: $scope.list_url,
        }).success(function (data, status) {
            var files = data.files;
            for (var i = 0; i < files.length; i++) {
                files[i].stamped_url = files[i].url + "?stamp=" + stamp;
            }
            $scope.filelist = files;
        });
    };

    var uploader = $scope.uploader = new FileUploader({
        url: $scope.upload_url
    });
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        fileItem.remove();
        if (response.result != 0) {
            $scope.showErrorAlert();
        }
    };
    uploader.onCompleteAll = function () {
        $scope.loadFiles();
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        fileItem.remove();
        $scope.loadFiles();
        $scope.showErrorAlert();
    };

    $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
        if (toState.name === $scope.tabStateName) {
            $scope.tab.activeIndex = $scope.tabIndex;
        }
    });

    // Workaround for issue with files not showing up after reopening popup from CKEditor.
    $timeout(function () {
        $scope.loadFiles();
    }, 100);

    $scope.controller = {
        isImage: function (name, type) {
            if (type)
                return (/(gif|jpg|jpeg|tiff|png|bmp)$/i).test(name);
            else
                return (/\.(gif|jpg|jpeg|tiff|png|bmp)$/i).test(name);
        }
    };
}

concertoPanel.controller('FileBrowserController', ["$scope", "$uibModal", "$window", "$timeout", "FileUploader", "$http", FileBrowserController]);
