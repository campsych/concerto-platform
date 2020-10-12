function ExportController($scope, $uibModalInstance, uiGridConstants, $http, ids, exportInstructionsPath) {

    $scope.exportFormat = 'yml';
    $scope.exportInstructions = [];
    $scope.exportInstructionsOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "exportInstructions",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.exportInstructionsOptions.enableFiltering = !$scope.exportInstructionsOptions.enableFiltering;
                    $scope.exportInstructionsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
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
                displayName: Trans.LIST_FIELD_DATA,
                name: "data",
                cellTemplate:
                '<div class="ui-grid-cell-contents">' +
                '<select ng-model="row.entity.data" style="width: 100%;">' +
                "<option value='0' ng-show='row.entity.class_name != \"DataTable\"'>" + Trans.LIST_FIELD_DATA_NOT_APPLICABLE + "</option>" +
                "<option value='1' ng-show='row.entity.class_name == \"DataTable\"'>" + Trans.LIST_FIELD_DATA_LEAVE + "</option>" +
                "<option value='2' ng-show='row.entity.class_name == \"DataTable\"'>" + Trans.LIST_FIELD_DATA_INCLUDE + "</option>" +
                '</select>' +
                '</div>'
            }, {
                displayName: Trans.LIST_FIELD_DATA_NUM,
                name: "data_num"
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.exportInstructionsGridApi = gridApi;
        }
    };

    function compactInstructions(instructions) {
        var result = {};
        for (var i = 0; i < instructions.length; i++) {
            var instruction = instructions[i];
            var className = instruction.class_name;
            if (!(className in result)) {
                result[className] = {
                    id: [],
                    data: [],
                    name: []
                }
            }
            result[className].id.push(instruction.id);
            result[className].data.push(instruction.data);
            result[className].name.push(instruction.name);
        }
        return result;
    }

    $scope.export = function () {
        $uibModalInstance.close({
            format: $scope.exportFormat,
            instructions: compactInstructions($scope.exportInstructions)
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };

    function fetchExportInstructions() {
        $http.post(exportInstructionsPath.pf(ids)).then(
            function success(response) {
                switch (response.data.result) {
                    case BaseController.RESULT_OK: {
                        $scope.exportInstructions = response.data.instructions;
                        break;
                    }
                }
            }
        );
    }

    fetchExportInstructions();
}