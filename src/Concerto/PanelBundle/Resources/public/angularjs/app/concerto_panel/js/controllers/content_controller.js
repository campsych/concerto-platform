function ContentController($scope, $state, $location) {
    $scope.tab = {
        activeIndex: -1
    };

    $scope.setTab = function (tab) {
        $state.go(tab, {}, {location: 'replace'});
    };

    $scope.setFirstActiveTab = function (index, tab) {
        if(location.hash) return;
        if ($scope.tab.activeIndex === -1) {

            $scope.tab.activeIndex = index;
            $scope.setTab(tab);
        }
    };
}

concertoPanel.controller('ContentController', ["$scope", "$state", "$location", ContentController]);