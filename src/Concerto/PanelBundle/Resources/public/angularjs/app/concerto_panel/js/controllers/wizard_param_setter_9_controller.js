/**
 * Group
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter9Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;

  if ($scope.output === null || $scope.output === undefined || typeof $scope.output !== 'object' || $scope.output.constructor === Array) {
    $scope.output = {};
  }
};

concertoPanel.controller('WizardParamSetter9Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter9Controller]);