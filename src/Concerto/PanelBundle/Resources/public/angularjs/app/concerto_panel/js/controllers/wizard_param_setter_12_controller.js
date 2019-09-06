/**
 * Data Table
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter12Controller($scope, DataTableCollectionService, AdministrationSettingsService) {
    $scope.dataTableCollectionService = DataTableCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onColumnMapTableChange = function () {
        let table = $scope.dataTableCollectionService.getBy('name', $scope.output.table);
        if (table !== null) {
            let tabCols = table.columns;
            for (let i = 0; i < $scope.param.definition.cols.length; i++) {
                let colDef = $scope.param.definition.cols[i];
                for (let j = 0; j < tabCols.length; j++) {
                    let colTab = tabCols[j];
                    if (colDef.name == colTab.name) {
                        if ($scope.output.columns === undefined)
                            $scope.output.columns = {};
                        $scope.output.columns[colDef.name] = colTab.name;
                        break;
                    }
                }
            }
        }
    };
}

concertoPanel.controller('WizardParamSetter12Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamSetter12Controller]);