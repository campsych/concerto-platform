testRunner.compileProvider.component('itemOptions', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/itemOptions/content?css=1&html=1&js=0",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: '<'
  },
  controller: function controller($scope) {
    $scope.choiceContainerStyle = {
    }

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
        $scope.choiceContainerStyle = {
          "grid-template-columns": "repeat(" + columnsNum + ", 1fr)"
        }
      } else {
        $scope.choiceContainerStyle = {
          "grid-template-columns": "repeat(" + responseOptions.options.length + ", 1fr)"
        }
      }
    }

    $scope.selectResponse = function(option) {
      $scope.response.value = option.value;
    }
  }
});
