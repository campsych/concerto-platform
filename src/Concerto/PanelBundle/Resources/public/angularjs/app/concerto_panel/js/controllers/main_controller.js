function MainController($scope, i18nService, RDocumentation, AdministrationSettingsService) {
    $scope.lang = "pl";
    $scope.RDocumentation = RDocumentation;
    AdministrationSettingsService.fetchSettingsMap();
}

concertoPanel.controller('MainController', ["$scope", "i18nService", "RDocumentation", "AdministrationSettingsService", MainController]);