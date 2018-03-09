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
    var tabCols = $scope.dataTableCollectionService.getBy('name', $scope.output.table).columns;
    for (var i = 0; i < $scope.param.definition.cols.length; i++) {
      var colDef = $scope.param.definition.cols[i];
      for (var j = 0; j < tabCols.length; j++) {
        var colTab = tabCols[j];
        if (colDef.name == colTab.name) {
          if ($scope.output.columns === undefined)
            $scope.output.columns = {};
          $scope.output.columns[colDef.name] = colTab.name;
          break;
        }
      }
    }
  };

  if ($scope.output === null || $scope.output === undefined || typeof $scope.output !== 'object' || $scope.output.constructor === Array) {
    $scope.output = {};
  }
};

concertoPanel.controller('WizardParamSetter12Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamSetter12Controller]);