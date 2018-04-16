/**
 * HTML
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter2Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.htmlEditorOptions = Defaults.ckeditorTestContentOptions;

  if ($scope.output === undefined || typeof $scope.output === 'object') {
    $scope.output = null;
  }
  if ($scope.output == null && $scope.param.definition != undefined) {
    $scope.output = $scope.param.definition.defvalue;
  }
  if ($scope.output === undefined || $scope.output === null) {
    $scope.output = "";
  }
  $scope.onPrimitiveValueChange($scope.output);
};

concertoPanel.controller('WizardParamSetter2Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter2Controller]);