function ImportController($scope, $uibModalInstance, $http, $uibModal, FileUploader, importPath, preImportStatusPath) {
    $scope.item = null;
    $scope.object.validationErrors = [];
    $scope.importPath = importPath;
    $scope.preImportStatusPath = preImportStatusPath;

    $scope.preImportStatus = [];
    $scope.colNameContent = function (entity) {
        var result = entity.name;
        if (entity.revision != 0) {
            result += " (r" + entity.revision + ")";
        }
        return result;
    };
    $scope.colExistsContent = function (entity) {
        var revision = "";
        if (entity.existing_object != null && entity.existing_object.revision != 0)
            revision += " (r" + entity.existing_object.revision + ")";
        var result = entity.existing_object != null ? ('<i class="glyphicon glyphicon-ok green"></i>' + revision) : '<i class="glyphicon glyphicon-remove red"></i>';
        return result;
    };
    $scope.colSafeContent = function (entity) {
        var safe = true;
        if (entity.action == 1) {
            //if (entity.revision == 0 || entity.starter_conent == 0 || entity.existing_object.revision == 0 || entity.existing_object.starter_content == 0)
            safe = false;
        }
        var result = '<i class="glyphicon glyphicon-ok green"></i>';
        if (!safe)
            result = '<i class="glyphicon glyphicon-remove red"></i>';
        return result;
    };
    $scope.preImportStatusOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "preImportStatus",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
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
                        '<div class="ui-grid-cell-contents" ng-bind-html="grid.appScope.colNameContent(row.entity)"></div>'
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
                        "<option value='1' ng-show='row.entity.existing_object!=null'>" + Trans.IMPORT_ACTION_CONVERT + "</option>" +
                        '</select>' +
                        '</div>'
            }, {
                displayName: Trans.LIST_FIELD_RENAME,
                field: "rename",
                cellTemplate:
                        '<div class="ui-grid-cell-contents">' +
                        '<input ng-model="row.entity.rename" style="width: 100%;" />' +
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

    // no way to use this module without constructing a new instance, unfortunately
    $scope.uploader = new FileUploader({
        autoUpload: true,
        url: Paths.FILE_UPLOAD

    });

    $scope.uploader.onCompleteItem = function (item, response, status, headers) {
        if (response.result === 0) {
            $scope.item = item;

            $http.post($scope.preImportStatusPath, {
                "file": $scope.item.file.name
            }).success(function (data) {
                $scope.preImportStatus = data.status;
            });
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
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.IMPORT_DIALOG_TITLE;
                },
                content: function () {
                    return Trans.DIALOG_MESSAGE_CONFIRM_UNSAFE_IMPORT;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $scope.persistImport();
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}