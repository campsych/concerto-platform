testRunner.compileProvider.component('itemOptions', {
  templateUrl: testRunner.settings.directory + "ViewTemplate/itemOptions/content?css=1,html=1",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: "<"
  },
  controller: function controller($scope) {
    $scope.item = this.item;
    $scope.response = this.response;
    $scope.responseRequired = this.responseRequired;

    $scope.selectResponse = function(option) {
      $scope.response.value = option.value;
    }
  }
});
