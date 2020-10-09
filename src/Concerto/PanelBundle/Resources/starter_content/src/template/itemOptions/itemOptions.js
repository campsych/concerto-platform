testRunner.compileProvider.component('itemOptions', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/itemOptions/content?css=1,html=1",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: '<'
  },
  controller: function controller($scope) {
    $scope.choiceStyle = {
      "flex-grow": 1
    };

    this.$onInit = function() {
      $scope.item = this.item;
      $scope.response = this.response;
      $scope.responseRequired = this.responseRequired;

      initStyles();
    };

    initStyles = function() {
      let responseOptions = $scope.item.responseOptions;
      let columnsNum = parseInt(responseOptions.optionsColumnsNum);
      if(!isNaN(columnsNum) && columnsNum > 0) {
        $scope.choiceStyle = {
          width: "calc(100% * (1/" + columnsNum + ") - 8px)"
        }
      }
    }

    $scope.selectResponse = function(option) {
      $scope.response.value = option.value;
    }
  }
});
