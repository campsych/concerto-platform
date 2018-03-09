/**
 * Data Table Column
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter7Controller($scope, DataTableCollectionService, AdministrationSettingsService) {
  $scope.dataTableCollectionService = DataTableCollectionService;
  $scope.administrationSettingsService = AdministrationSettingsService;

  if ($scope.output === null || $scope.output === undefined || typeof $scope.output !== 'object' || $scope.output.constructor === Array) {
    $scope.output = {};
  }
};

concertoPanel.controller('WizardParamSetter7Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamSetter7Controller]);