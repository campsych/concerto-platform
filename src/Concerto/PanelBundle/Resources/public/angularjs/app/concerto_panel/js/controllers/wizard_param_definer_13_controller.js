/**
 * Test Wizard
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner13Controller($scope, AdministrationSettingsService, TestCollectionService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.testCollectionService = TestCollectionService;

};

concertoPanel.controller('WizardParamDefiner13Controller', ["$scope", "AdministrationSettingsService", "TestCollectionService", WizardParamDefiner13Controller]);