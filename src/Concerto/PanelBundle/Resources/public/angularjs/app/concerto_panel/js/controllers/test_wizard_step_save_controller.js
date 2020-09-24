function TestWizardStepSaveController($scope, $uibModalInstance, $http, $timeout, object, wizard) {
    $scope.object = object;
    $scope.wizard = wizard;
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
        obj.objectTimestamp = $scope.wizard.updatedOn;
        return obj;
    };

    $scope.persist = function () {
        $scope.object.validationErrors = [];

        var oid = $scope.object.id;

        var addModalDialog = $uibModalInstance;
        $http.post(Paths.TEST_WIZARD_STEP_SAVE.pf(oid), $scope.getPersistObject()).then(function (httpResponse) {

            switch (httpResponse.data.result) {
                case BaseController.RESULT_OK: {
                    if (addModalDialog != null) {
                        addModalDialog.close($scope.object);
                    }
                    break;
                }
                case BaseController.RESULT_VALIDATION_FAILED: {
                    $scope.object.validationErrors = httpResponse.data.errors;
                    $timeout(() => {
                        let alert = $(".alert-danger").first();
                        if(alert.length > 0) alert[0].scrollIntoView({behavior: "smooth"});
                    });
                    break;
                }
            }
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}