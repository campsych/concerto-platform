/**
 * ViewTemplate
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter5Controller($scope, ViewTemplateCollectionService, AdministrationSettingsService) {
    $scope.viewTemplateCollectionService = ViewTemplateCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter5Controller', ["$scope", "ViewTemplateCollectionService", "AdministrationSettingsService", WizardParamSetter5Controller]);