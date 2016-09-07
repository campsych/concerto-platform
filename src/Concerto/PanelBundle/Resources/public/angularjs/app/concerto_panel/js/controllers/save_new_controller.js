function SaveNewController($scope, $uibModalInstance, $http, $uibModal, name, saveNewPath) {

    $scope.saveNewPath = saveNewPath;
    $scope.object.validationErrors = [];
    $scope.name = name;

    $scope.persistNew = function () {
        $scope.object.validationErrors = [];

        $http.post($scope.saveNewPath.pf($scope.object.id), {
            "name": $scope.name
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
                                        return Trans.SAVE_NEW_DIALOG_TITLE_MAIN;
                                    },
                                    content: function () {
                                        return Trans.SAVE_NEW_DIALOG_MESSAGE_COPIED;
                                    },
                                    type: function () {
                                        return "success";
                                    }
                                }
                            });

                            $uibModalInstance.close(response.data.object);
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
                                return Trans.SAVE_NEW_DIALOG_TITLE_MAIN;
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
        $scope.persistNew();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}