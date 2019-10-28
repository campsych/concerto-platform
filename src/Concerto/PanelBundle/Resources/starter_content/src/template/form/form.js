testRunner.controllerProvider.register("form", function ($scope) {
  $scope.instructions = testRunner.R.instructions;
  $scope.fields = angular.fromJson(testRunner.R.fields);
  $scope.values = angular.fromJson(testRunner.R.initialValues);
  if (typeof $scope.values === 'undefined' || $scope.values === null) $scope.values = {};
  $scope.buttonLabel = testRunner.R.buttonLabel ? testRunner.R.buttonLabel : "Next";

  function initializeValues() {
    for (var i = 0; i < $scope.fields.length; i++) {
      var field = $scope.fields[i];

      if (typeof $scope.values[field.name] === 'undefined') {
        $scope.values[field.name] = "";
      }
    }
  }

  initializeValues();
});
