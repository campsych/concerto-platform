/**
 * Data Table
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter6Controller($scope, DataTableCollectionService, AdministrationSettingsService) {
    $scope.dataTableCollectionService = DataTableCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter6Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamSetter6Controller]);