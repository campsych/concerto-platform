function ImportController($scope, $uibModalInstance, $http, $uibModal, FileUploader, importPath, preImportStatusPath, uiGridConstants, DialogsService) {
  $scope.item = null;
  $scope.object.validationErrors = [];
  $scope.importPath = importPath;
  $scope.preImportStatusPath = preImportStatusPath;

  $scope.preImportStatus = [];
  $scope.dialogsService = DialogsService;
  $scope.dirty = false;

  $scope.colExistsContent = function (entity) {
    return entity.existing_object ? '<i class="glyphicon glyphicon-ok green"></i>' : '<i class="glyphicon glyphicon-remove red"></i>';
  };
  $scope.colSafeContent = function (entity) {
    var safe = true;
    if (entity.action == 1) {
      safe = false;
    }
    var result = '<i class="glyphicon glyphicon-ok green"></i>';
    if (!safe)
      result = '<i class="glyphicon glyphicon-remove red"></i>';
    return result;
  };
  $scope.isImportSafe = function () {
    for (var i = 0; i < $scope.preImportStatus.length; i++) {
      var ins = $scope.preImportStatus[i];
      if (ins.action == 1)
        return false;
    }
    return true;
  };
  $scope.preImportStatusOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    data: "preImportStatus",
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.preImportStatusOptions.enableFiltering = !$scope.preImportStatusOptions.enableFiltering;
          $scope.preImportStatusGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    columnDefs: [
      {
        displayName: Trans.LIST_FIELD_ID,
        field: "id",
        type: "number",
        width: 75
      }, {
        displayName: Trans.LIST_FIELD_NAME,
        name: "name",
        cellTemplate:
            '<div class="ui-grid-cell-contents" ng-bind-html="row.entity.name"></div>'
      }, {
        displayName: Trans.LIST_FIELD_TYPE,
        field: "class_name"
      }, {
        displayName: Trans.LIST_FIELD_EXISTS,
        name: "exists",
        cellTemplate: '<div class="ui-grid-cell-contents" ng-bind-html="grid.appScope.colExistsContent(row.entity)" style="text-align: center;"></div>'
      }, {
        displayName: Trans.LIST_FIELD_ACTION,
        name: "action",
        cellTemplate:
        '<div class="ui-grid-cell-contents">' +
        '<select ng-model="row.entity.action" style="width: 100%;">' +
        "<option value='0'>" + Trans.IMPORT_ACTION_NEW + "</option>" +
        "<option value='1' ng-show='row.entity.existing_object'>" + Trans.IMPORT_ACTION_CONVERT + "</option>" +
        "<option value='2' ng-show='row.entity.can_ignore'>" + Trans.IMPORT_ACTION_IGNORE + "</option>" +
        '</select>' +
        '</div>'
      }, {
        displayName: Trans.LIST_FIELD_RENAME,
        field: "rename",
        cellTemplate:
        '<div class="ui-grid-cell-contents">' +
        '<input ng-model="row.entity.rename" style="width: 100%;" ng-disabled="row.entity.action == \'2\'" />' +
        '</div>'
      }, {
        displayName: Trans.LIST_FIELD_SAFE,
        name: "safe",
        cellTemplate: '<div class="ui-grid-cell-contents" ng-bind-html="grid.appScope.colSafeContent(row.entity)" style="text-align: center;"></div>'
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.preImportStatusGridApi = gridApi;
    }
  };

  $scope.getFileName = function () {
    return $scope.item.file.name;
  };

  $scope.showErrorAlert = function (content) {
    if (content == null) {
      content = Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_MESSAGE;
    }

    $scope.dialogsService.alertDialog(
        Trans.FILE_BROWSER_ALERT_UPLOAD_FAILED_TITLE,
        content,
        "danger"
    );
    $scope.cancel();
  };

  $scope.uploader = new FileUploader({
    autoUpload: true,
    url: Paths.FILE_UPLOAD,
    formData: [{
      dir: 0 //private
    }]
  });

  $scope.uploader.onCompleteItem = function (item, response, status, headers) {
    if (response.result === 0) {
      $scope.item = item;

      $http.post($scope.preImportStatusPath, {
        "file": $scope.item.file.name
      }).then(
          function successCallback(response) {
            switch (response.data.result) {
              case BaseController.RESULT_OK: {
                $scope.preImportStatus = response.data.status;
                break;
              }
              case 2: {
                $scope.showErrorAlert(Trans.IMPORT_MESSAGE_VERSION_INCOMPATIBLE);
                break;
              }
            }
          },
          function errorCallback() {
            $scope.dialogsService.alertDialog(
                Trans.DIALOG_TITLE_SAVE,
                Trans.DIALOG_MESSAGE_FAILED,
                "danger"
            );
            $scope.cancel();
          });
    } else {
      $scope.showErrorAlert();
    }
  };

  $scope.persistImport = function () {
    $scope.object.validationErrors = [];

    $http.post($scope.importPath, {
      "file": $scope.item.file.name,
      "instructions": angular.toJson($scope.preImportStatus)
    }).then(
        function successCallback(response) {
          switch (response.data.result) {
            case BaseController.RESULT_OK: {
              $scope.dialogsService.alertDialog(
                  Trans.IMPORT_DIALOG_TITLE,
                  Trans.IMPORT_DIALOG_MESSAGE_IMPORTED,
                  "success"
              );

              $uibModalInstance.close($scope.object);
              break;
            }
            case BaseController.RESULT_VALIDATION_FAILED: {
              $scope.object.validationErrors = response.data.errors;
              $(".modal").animate({scrollTop: 0}, "slow");
              $scope.dirty = true;
              break;
            }
            case 2: {
              $scope.showErrorAlert(Trans.IMPORT_MESSAGE_VERSION_INCOMPATIBLE);
              break;
            }
          }
        },
        function errorCallback() {
          $scope.dialogsService.alertDialog(
              Trans.DIALOG_TITLE_SAVE,
              Trans.DIALOG_MESSAGE_FAILED,
              "danger"
          );
          $scope.dirty = true;
        });
  };

  $scope.save = function () {
    if (!$scope.isImportSafe()) {
      $scope.dialogsService.confirmDialog(
          Trans.IMPORT_DIALOG_TITLE,
          Trans.DIALOG_MESSAGE_CONFIRM_UNSAFE_IMPORT,
          function (response) {
            $scope.persistImport();
          }
      );
    } else {
      $scope.persistImport();
    }
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss($scope.dirty);
  };
}