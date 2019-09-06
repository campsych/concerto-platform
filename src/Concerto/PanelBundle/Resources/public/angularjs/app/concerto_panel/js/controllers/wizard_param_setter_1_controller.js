/**
 * Multi Line
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter1Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter1Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter1Controller]);