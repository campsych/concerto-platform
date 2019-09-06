/**
 * Group
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter9Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;
}

concertoPanel.controller('WizardParamSetter9Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter9Controller]);