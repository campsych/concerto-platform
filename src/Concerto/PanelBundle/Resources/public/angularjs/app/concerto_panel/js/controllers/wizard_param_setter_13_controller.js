/**
 * Test Wizard
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter13Controller($scope, TestCollectionService, AdministrationSettingsService, TestWizardParam) {
    $scope.testCollectionService = TestCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.$watch('param.definition.test', function (newTest, oldTest) {
        if (newTest === null || newTest === undefined)
            return;
        $scope.object = angular.copy($scope.testCollectionService.getBy("name", newTest));
    });
}

concertoPanel.controller('WizardParamSetter13Controller', ["$scope", "TestCollectionService", "AdministrationSettingsService", "TestWizardParam", WizardParamSetter13Controller]);