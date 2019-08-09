function DataTableStructureSaveController($scope, $uibModalInstance, $http, table, object) {
    $scope.oldName = object.name;
    if ($scope.oldName === "") {
        $scope.oldName = "0";
    }
    $scope.object = object;
    $scope.table = table;
    $scope.dialogTitle = "";
    $scope.dialogSuccessfulMessage = "";

    $scope.types = [
        {label: "string", value: "string"},
        {label: "integer", value: "integer"},
        {label: "smallint", value: "smallint"},
        {label: "bigint", value: "bigint"},
        {label: "boolean", value: "boolean"},
        {label: "decimal", value: "decimal"},
        {label: "date", value: "date"},
        {label: "datetime", value: "datetime"},
        {label: "text", value: "text"},
        {label: "float", value: "float"}
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
                        if (addModalDialog != null) {
                            addModalDialog.close($scope.object);
                        }
                        break;
                    }
                    case BaseController.RESULT_VALIDATION_FAILED: {
                        $scope.object.validationErrors = response.data.errors;
                        $(".modal").animate({scrollTop: 0}, "slow");
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