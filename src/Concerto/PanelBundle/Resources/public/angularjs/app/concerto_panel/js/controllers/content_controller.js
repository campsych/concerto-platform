function ContentController($scope, $state, $location) {
    $scope.tabsCollection = [{
        name: "tests",
    }, {
        name: "templates",
    }, {
        name: "tables",
    }, {
        name: "files",
    }, {
        name: "users",
    }, {
        name: "wizards",
    }, {
        name: "administration"
    }];

    $scope.tab = {
        activeIndex: -1
    };

    $scope.goToTab = function(index){
        $scope.tab.activeIndex = index;
        $state.go($scope.tabsCollection[index].name, {}, {location: 'replace'});
    }

    $scope.setFirstActiveTab = function (index) {
        if (location.hash) return;
        if ($scope.tab.activeIndex === -1) {
            $scope.goToTab(index);
        }
    };

    $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
        for (var i = 0; i < $scope.tabsCollection.length; i++) {
            var tab = $scope.tabsCollection[i];

            if (tab.name == toState.name || tab.name + "Form" == toState.name) {
                $scope.tab.activeIndex = i;
                break;
            }
        }
    });
}

concertoPanel.controller('ContentController', ["$scope", "$state", "$location", ContentController]);