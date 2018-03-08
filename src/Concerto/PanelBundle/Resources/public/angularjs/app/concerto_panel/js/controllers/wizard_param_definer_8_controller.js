/**
 * Test
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner8Controller($scope, TestCollectionService, AdministrationSettingsService) {
  $scope.testCollectionService = TestCollectionService;
  $scope.administrationSettingsService = AdministrationSettingsService;
};

concertoPanel.controller('WizardParamDefiner8Controller', ["$scope", "TestCollectionService", "AdministrationSettingsService", WizardParamDefiner8Controller]);