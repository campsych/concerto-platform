function DataTableStructureSaveController($scope, $uibModalInstance, $http, $timeout, table, object) {
    $scope.oldName = object.name;
    if ($scope.oldName === "") {
        $scope.oldName = "0";
    }
    $scope.object = object;
    $scope.table = table;
    $scope.dialogTitle = "";
    $scope.dialogSuccessfulMessage = "";

    $scope.types = [
        {label: "boolean", value: "boolean"},
        {label: "bigint", value: "bigint"},
        {label: "date", value: "date"},
        {label: "datetime", value: "datetime"},
        {label: "decimal", value: "decimal"},
        {label: "float", value: "float"},
        {label: "integer", value: "integer"},
        {label: "json", value: "json"},
        {label: "smallint", value: "smallint"},
        {label: "string", value: "string"},
        {label: "text", value: "text"}
    ];

    if ($scope.object.id === 0) {
        $scope.dialogTitle = Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_ADD;
    } else {
        $scope.dialogTitle = Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_EDIT;
    }

    $scope.save = function () {
        $scope.persist();
    };

    $scope.persist = function () {
        $scope.object.validationErrors = [];
        $scope.object.systemErrors = [];

        var addModalDialog = $uibModalInstance;
        $http.post(Paths.DATA_TABLE_COLUMNS_SAVE.pf($scope.table.id, $scope.oldName), angular.extend(
            {}, $scope.object, {objectTimestamp: $scope.table.updatedOn}
        )).then(
            function successCallback(response) {
                switch (response.data.result) {
                    case BaseController.RESULT_OK: {
                        // no need to update timestamp as collection will be refreshed
                        if (addModalDialog != null) {
                            addModalDialog.close($scope.object);
                        }
                        break;
                    }
                    case BaseController.RESULT_VALIDATION_FAILED: {
                        $scope.object.validationErrors = response.data.errors;
                        $timeout(() => {
                            let alert = $(".alert-danger").first();
                            if(alert.length > 0) alert[0].scrollIntoView({behavior: "smooth"});
                        });
                        break;
                    }
                }
            },
            function errorCallback(response) {
                $scope.object.systemErrors = [
                    Trans.DIALOG_MESSAGE_FAILED
                ];
            }
        );
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}