function FileBrowserController($scope, $uibModal, $window, $timeout, FileUploader, $http, DialogsService) {
  $scope.tabStateName = "files";

  $scope.delete_url = Paths.FILE_UPLOAD_DELETE;
  $scope.list_url = Paths.FILE_UPLOAD_LIST;
  $scope.upload_url = Paths.FILE_UPLOAD;

  $scope.dialogsService = DialogsService;

  /**
   * Saves the selected file into CKEditor.
   *
   * This function is only used (and usable) when the file browser is open from within the CKEditor.
   */
  $scope.submitResult = function (cke_callback, url) {
    $window.opener.CKEDITOR.tools.callFunction(cke_callback, url);
    $window.close();
  };

  $scope.showErrorAlert = function () {
    $scope.dialogsService.alertDialog(
        Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE,
        Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE,
        "danger"
    );
  };

  $scope.remove = function (file) {
    $scope.dialogsService.confirmDialog(
        Trans.DIALOG_TITLE_DELETE,
        Trans.DIALOG_MESSAGE_CONFIRM_DELETE,
        function (response) {
          $http.post($scope.delete_url + encodeURIComponent(file.name), {}).success(function (data) {
            if (data.result == 0) {
              $scope.loadFiles();
            }
          });
        }
    );
  };

  $scope.loadFiles = function () {
    var stamp = new Date().getTime();
    $http({
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
    url: $scope.upload_url,
    formData: [{
      dir: 1 //public
    }]
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

concertoPanel.controller('FileBrowserController', ["$scope", "$uibModal", "$window", "$timeout", "FileUploader", "$http", "DialogsService", FileBrowserController]);
