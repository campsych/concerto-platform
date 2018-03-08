/**
 * Multi line
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner1Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
};

concertoPanel.controller('WizardParamDefiner1Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner1Controller]);