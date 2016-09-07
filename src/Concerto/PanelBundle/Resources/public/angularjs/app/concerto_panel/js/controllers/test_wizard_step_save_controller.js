function TestWizardStepSaveController($scope, $uibModalInstance, $http, object) {
    $scope.savePath = Paths.TEST_WIZARD_STEP_SAVE;

    $scope.object = object;
    $scope.dialogTitle = "";
    $scope.dialogSuccessfulMessage = "";
    $scope.editorOptions = Defaults.ckeditorPanelContentOptions;

    if ($scope.object.id === 0) {
        $scope.dialogTitle = Trans.TEST_WIZARD_STEP_DIALOG_TITLE_ADD;
    } else {
        $scope.dialogTitle = Trans.TEST_WIZARD_STEP_DIALOG_TITLE_EDIT;
    }

    $scope.save = function () {
        $scope.persist();
    };

    $scope.getPersistObject = function () {
        var obj = angular.copy($scope.object);
        delete obj.params;
        return obj;
    };

    $scope.persist = function () {
        $scope.object.validationErrors = [];

        var oid = $scope.object.id;

        var addModalDialog = $uibModalInstance;
        $http.post($scope.savePath.pf(oid), $scope.getPersistObject()).success(function (data) {

            switch (data.result) {
                case BaseController.RESULT_OK:
                {
                    if (addModalDialog != null) {
                        addModalDialog.close($scope.object);
                    }
                    break;
                }
                case BaseController.RESULT_VALIDATION_FAILED:
                {
                    $scope.object.validationErrors = data.errors;
                    $("html, body").animate({scrollTop: 0}, "slow");$(".modal").animate({scrollTop: 0}, "slow");break;
                }
            }
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}