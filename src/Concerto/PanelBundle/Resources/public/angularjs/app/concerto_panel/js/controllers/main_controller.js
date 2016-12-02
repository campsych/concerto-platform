function MainController($scope, i18nService, RDocumentation) {
    $scope.lang = "pl";
    $scope.RDocumentation = RDocumentation;
}

concertoPanel.controller('MainController', ["$scope", "i18nService", "RDocumentation", MainController]);