testRunner.compileProvider.component('inputSelect', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/inputSelect/content?css=1&html=1&js=0",
  bindings: {
    field: '=',
    values: '='
  },
  controller: function controller($scope) {
    this.$onInit = function() {
      $scope.field = this.field;
      $scope.values = this.values;
    };

    $scope.getValidator = function (type) {
      if (typeof this.field.validation === 'undefined') return null;
      for (var i = 0; i < this.field.validation.length; i++) {
        var validation = this.field.validation[i];
        if (validation.type === type) {
          return validation;
        }
      }
      return null;
    };
  }
});
