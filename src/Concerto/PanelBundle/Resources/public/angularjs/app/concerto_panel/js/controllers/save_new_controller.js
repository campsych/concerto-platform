function SaveNewController($scope, $uibModalInstance, $http, $uibModal, name, saveNewPath, DialogsService) {

    $scope.saveNewPath = saveNewPath;
    $scope.object.validationErrors = [];
    $scope.name = name;
    $scope.dialogsService = DialogsService;

    $scope.persistNew = function () {
        $scope.object.validationErrors = [];

        $http.post($scope.saveNewPath.pf($scope.object.id), {
            "name": $scope.name
        }).then(
                function successCallback(response) {
                    switch (response.data.result) {
                        case BaseController.RESULT_OK:
                        {
                            $scope.dialogsService.alertDialog(
                                    Trans.SAVE_NEW_DIALOG_TITLE_MAIN,
                                    Trans.SAVE_NEW_DIALOG_MESSAGE_COPIED,
                                    "success"
                                    );

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
                    $scope.dialogsService.alertDialog(
                            Trans.SAVE_NEW_DIALOG_TITLE_MAIN,
                            Trans.DIALOG_MESSAGE_FAILED,
                            "danger"
                            );
                });
    };

    $scope.save = function () {
        $scope.persistNew();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}