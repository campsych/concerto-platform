/**
 * Select
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter3Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter3Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter3Controller]);