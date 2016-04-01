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

    $scope.moveParamDown = function (index) {
        var params = $filter('orderBy')($scope.step.params, "order");
        for (var i = 0; i < params.length; i++) {
            var param = params[i];
            if (param.order !== i) {
                param.order = i;
            }
            if (index === i) {
                params[i - 1].order++;
                param.order--;
            }
        }
    };

    $scope.moveParamUp = function (index) {
        var params = $filter('orderBy')($scope.step.params, "order");
        var paramFound = false;
        for (var i = 0; i < params.length; i++) {
            var param = params[i];
            if (param.order !== i) {
                param.order = i;
            }
            if (paramFound) {
                param.order--;
                params[i - 1].order++;
                paramFound = false;
            }
            if (index === i) {
                paramFound = true;
            }
        }
    };

    $scope.mapParamsValue();
}

concertoPanel.controller('TestWizardCarouselStepController', ["$scope", "$http", "$filter", "TestWizardParam", TestWizardCarouselStepController]);