testRunner.compileProvider.component('itemOpen', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/itemOpen/content?css=1&html=1&js=0",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: '<'
  },
  controller: function controller($scope) {
    this.$onInit = function() {
      $scope.item = this.item;
      $scope.response = this.response;
      $scope.responseRequired = this.responseRequired;
    }
  }
});
