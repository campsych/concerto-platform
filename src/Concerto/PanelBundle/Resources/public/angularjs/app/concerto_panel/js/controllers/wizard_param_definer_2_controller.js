/**
 * HTML
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner2Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.htmlEditorOptions = Defaults.ckeditorPanelContentOptions;
};

concertoPanel.controller('WizardParamDefiner2Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner2Controller]);