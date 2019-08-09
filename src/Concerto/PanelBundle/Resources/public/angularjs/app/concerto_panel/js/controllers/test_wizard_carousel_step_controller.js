function TestWizardCarouselStepController($scope, $http, $filter, TestWizardParam) {
    $scope.testWizardParamsService = TestWizardParam;
    $scope.values = {};

    $scope.filterByGuiEligible = function (obj) {
        return typeof (obj.exposed) === 'undefined' || obj.exposed == 0;
    };

    $scope.mapParamsValue = function () {
        $scope.values = {};
        for (var i = 0; i < $scope.step.params.length; i++) {
            var param = $scope.step.params[i];
            TestWizardParam.unserializeParamValue(param);
            $scope.values[param.name] = param.output;
        }
    };

    $scope.mapParamsValue();
}

concertoPanel.controller('TestWizardCarouselStepController', ["$scope", "$http", "$filter", "TestWizardParam", TestWizardCarouselStepController]);