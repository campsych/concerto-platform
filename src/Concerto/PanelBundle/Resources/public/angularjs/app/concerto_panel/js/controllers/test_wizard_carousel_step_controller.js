function TestWizardCarouselStepController($scope, $http, $filter, TestWizardParam) {

    $scope.savePath = Paths.TEST_WIZARD_PARAM_SAVE;
    $scope.testWizardParamsService = TestWizardParam;

    $scope.values = {};

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