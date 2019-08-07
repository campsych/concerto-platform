function MainController($scope, i18nService, RDocumentation, AdministrationSettingsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AuthService) {
    $scope.lang = "pl";
    $scope.RDocumentation = RDocumentation;

    AuthService.fetchAuthUser(function () {
        AdministrationSettingsService.fetchSettingsMap();
        DataTableCollectionService.fetchObjectCollection();
        TestCollectionService.fetchObjectCollection();
        TestWizardCollectionService.fetchObjectCollection();
        ViewTemplateCollectionService.fetchObjectCollection();
        UserCollectionService.fetchObjectCollection();
    });
}

concertoPanel.controller('MainController', ["$scope", "i18nService", "RDocumentation", "AdministrationSettingsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "AuthService", MainController]);