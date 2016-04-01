function FileBrowserController($scope, $uibModal, $window, $timeout, FileUploader, $http, $state, $filter) {
    $scope.tabStateName = "files";
    $scope.tabIndex = 3;
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state);
    $scope.exportable = false;
    $scope.reloadOnModification = true;

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
            var httpRequest = $http({
                method: 'GET',
                url: $scope.delete_url + file.name,
            }).success(function (data, status) {
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
    };
    uploader.onCompleteAll = function () {
        $scope.loadFiles();
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        fileItem.remove();
        $scope.loadFiles();
    };

    // Workaround for issue with files not showing up after reopening popup from CKEditor.
    $timeout(function () {
        $scope.loadFiles();
    }, 100);

    var controller = $scope.controller = {
        isImage: function (name, type) {
            if (type)
                return (/(gif|jpg|jpeg|tiff|png|bmp)$/i).test(name);
            else
                return (/\.(gif|jpg|jpeg|tiff|png|bmp)$/i).test(name);
        }
    };
}

FileBrowserController.prototype = Object.create(BaseController.prototype);
concertoPanel.controller('FileBrowserController', ["$scope", "$uibModal", "$window", "$timeout", "FileUploader", "$http", "$state", "$filter", FileBrowserController]);
