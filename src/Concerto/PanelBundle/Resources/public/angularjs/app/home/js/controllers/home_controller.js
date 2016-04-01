function HomeController($scope, $http) {
    $scope.featuredCollectionPath = Paths.HOME_FEATURED_COLLECTION;
    $scope.testRunnerStart = Paths.TEST_RUNNER_START;

    $scope.testsCollection = [];
    $scope.test = null;
    $scope.showAlert = false;

    $scope.fetchTestsCollection = function() {
        $http.get($scope.featuredCollectionPath).success(function(collection) {
            $scope.testsCollection = collection;
        });
    };

    $scope.runTest = function() {
        if ($scope.test == null) {
            $scope.showAlert = true;
        } else {
            $scope.showAlert = false;
            location.href = $scope.testRunnerStart.pf($scope.test.slug);
        }
    };

    $scope.fetchTestsCollection();
}

home.controller('HomeController', ["$scope", "$http", HomeController]);