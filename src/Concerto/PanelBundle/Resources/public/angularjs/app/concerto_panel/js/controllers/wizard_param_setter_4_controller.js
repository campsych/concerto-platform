/**
 * Checkbox
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter4Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter4Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter4Controller]);