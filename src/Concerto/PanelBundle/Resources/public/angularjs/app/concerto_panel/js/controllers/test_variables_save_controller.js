function TestVariablesSaveController($scope, $uibModalInstance, $http, $timeout, object, test) {
    $scope.object = object;
    $scope.test = test;
    $scope.dialogTitle = "";
    $scope.dialogSuccessfulMessage = "";
    $scope.editorOptions = Defaults.ckeditorPanelContentOptions;

    switch ($scope.object.type) {
        case 0:
            if ($scope.object.id === 0) {
                $scope.dialogTitle = Trans.TEST_VARS_PARAMS_DIALOG_TITLE_ADD;
            } else {
                $scope.dialogTitle = Trans.TEST_VARS_PARAMS_DIALOG_TITLE_EDIT;
            }
            break;
        case 1:
            if ($scope.object.id === 0) {
                $scope.dialogTitle = Trans.TEST_VARS_RETURNS_DIALOG_TITLE_ADD;
            } else {
                $scope.dialogTitle = Trans.TEST_VARS_RETURNS_DIALOG_TITLE_EDIT;
            }
            break;
        case 2:
            if ($scope.object.id === 0) {
                $scope.dialogTitle = Trans.TEST_VARS_BRANCHES_DIALOG_TITLE_ADD;
            } else {
                $scope.dialogTitle = Trans.TEST_VARS_BRANCHES_DIALOG_TITLE_EDIT;
            }
            break;
    }

    $scope.save = function () {
        $scope.persist();
    };

    $scope.persist = function () {
        $scope.object.validationErrors = [];

        var oid = $scope.object.id;

        var addModalDialog = $uibModalInstance;
        $http.post(Paths.TEST_VARIABLE_SAVE.pf(oid),
            angular.extend({}, $scope.object, {objectTimestamp: $scope.test.updatedOn})
        ).then(function (httpResponse) {
            switch (httpResponse.data.result) {
                case BaseController.RESULT_OK: {
                    // no need to update timestamp as collection will be refreshed
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