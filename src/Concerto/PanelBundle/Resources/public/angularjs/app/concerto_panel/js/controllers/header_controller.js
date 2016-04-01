function HeaderController($scope) {

    $scope.logout = function() {
        location.href = Paths.LOGOUT;
    };

    $scope.changeLocale = function(key) {
        location.href = Paths.LOCALE.pf(key);
    };
}

concertoPanel.controller('HeaderController', ["$scope", HeaderController]);