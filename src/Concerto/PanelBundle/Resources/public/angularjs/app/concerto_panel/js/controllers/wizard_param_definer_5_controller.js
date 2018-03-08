/**
 * ViewTemplate
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner5Controller($scope, ViewTemplateCollectionService, AdministrationSettingsService) {
  $scope.viewTemplateCollectionService = ViewTemplateCollectionService;
  $scope.administrationSettingsService = AdministrationSettingsService;
};

concertoPanel.controller('WizardParamDefiner5Controller', ["$scope", "ViewTemplateCollectionService", "AdministrationSettingsService", WizardParamDefiner5Controller]);