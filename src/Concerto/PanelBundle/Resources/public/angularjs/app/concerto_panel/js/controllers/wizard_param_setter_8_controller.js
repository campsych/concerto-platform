/**
 * Test
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter8Controller($scope, TestCollectionService, AdministrationSettingsService) {
    $scope.testCollectionService = TestCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter8Controller', ["$scope", "TestCollectionService", "AdministrationSettingsService", WizardParamSetter8Controller]);