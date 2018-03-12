/**
 * Test Wizard
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter13Controller($scope, TestCollectionService, AdministrationSettingsService, TestWizardParam) {
  $scope.testCollectionService = TestCollectionService;
  $scope.administrationSettingsService = AdministrationSettingsService;

  $scope.validateOutput = function () {
    if ($scope.output === null || $scope.output === undefined || typeof $scope.output !== 'object' || $scope.output.constructor === Array) {
      $scope.output = {};
    }
  };

  $scope.validateOutput();
  $scope.$watch('param.definition.test', function (newTest, oldTest) {
    if (newTest === null || newTest === undefined)
      return;
    $scope.object = angular.copy($scope.testCollectionService.getBy("name", newTest));
    $scope.validateOutput();
  });
};

concertoPanel.controller('WizardParamSetter13Controller', ["$scope", "TestCollectionService", "AdministrationSettingsService", "TestWizardParam", WizardParamSetter13Controller]);