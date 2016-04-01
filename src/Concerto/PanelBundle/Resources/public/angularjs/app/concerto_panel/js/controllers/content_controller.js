function ContentController($scope, $state) {
    $scope.tab = {
        activeIndex: -1
    };

    $scope.setTab = function (tab) {
        $state.go(tab, {}, {location: 'replace'});
    };

    $scope.setFirstActiveTab = function (index, tab) {
        if ($scope.tab.activeIndex === -1){
            $scope.tab.activeIndex = index;
            $scope.setTab(tab);
        }
    };
}

concertoPanel.controller('ContentController', ["$scope", "$state", ContentController]);