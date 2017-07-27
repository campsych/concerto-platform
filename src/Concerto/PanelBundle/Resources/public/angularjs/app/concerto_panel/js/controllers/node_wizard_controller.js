function NodeWizardController($scope, $uibModalInstance, TestWizardParam, TestWizardCollectionService, node, test) {
    $scope.node = node;
    $scope.object = test;
    $scope.originalTest = angular.copy(test);

    for (var i = 0; i < $scope.object.variables.length; i++) {
        var variable = $scope.object.variables[i];
        var originalVariable = $scope.originalTest.variables[i];
        for (var j = 0; j < $scope.node.ports.length; j++) {
            var port = $scope.node.ports[j];
            if (variable.id === port.variable) {
                variable.value = port.value;
                variable.defaultValue = originalVariable.value === port.value ? "1" : "0";
            }
        }
    }
    TestWizardParam.testVariablesToWizardParams($scope.object.variables, $scope.object.steps);

    $scope.assignVarsToPorts = function () {
        for (var i = 0; i < $scope.object.variables.length; i++) {
            var variable = $scope.object.variables[i];
            var originalVariable = $scope.originalTest.variables[i];
            for (var j = 0; j < $scope.node.ports.length; j++) {
                var port = $scope.node.ports[j];
                if (variable.id === port.variable) {
                    port.value = variable.value;
                    port.defaultValue = originalVariable.value === variable.value ? "1" : "0";
                }
            }
        }
    };

    $scope.change = function () {
        TestWizardParam.wizardParamsToTestVariables($scope.object, $scope.object.steps, $scope.object.variables);
        $scope.assignVarsToPorts();
        $uibModalInstance.close($scope.node);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };
}