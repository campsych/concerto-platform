/**
 * ViewTemplate
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner6Controller($scope, DataTableCollectionService, AdministrationSettingsService) {
  $scope.dataTableCollectionService = DataTableCollectionService;
  $scope.administrationSettingsService = AdministrationSettingsService;
};

concertoPanel.controller('WizardParamDefiner6Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamDefiner6Controller]);