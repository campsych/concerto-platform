testRunner.controllerProvider.register("test", function ($scope) {
  $scope.items = testRunner.R.items;
  $scope.canGoBack = testRunner.R.canGoBack == 1;
  $scope.responseRequired = testRunner.R.responseRequired == 1;
  $scope.responseRequiredAlert = testRunner.R.responeRequiredAlert;
  $scope.page = testRunner.R.page;
  $scope.totalPages = testRunner.R.totalPages;
  $scope.pastResponses = testRunner.R.responses;
  $scope.responses = {};
  $scope.nextButtonLabel = testRunner.R.nextButtonLabel;
  $scope.backButtonLabel = testRunner.R.backButtonLabel;
  $scope.showPageInfo = testRunner.R.showPageInfo == 1;

  $scope.isFormValid = function() {
    if(!$scope.responseRequired) {
      return true;
    }
    for(let i=0; i<$scope.items.length; i++) {
      let item = $scope.items[i];
      let response = $scope.responses["r" + item.id];
      if(response.skipped) continue;
      if(!response.isValid()) return false;
    }
    return true;
  }

  if ($scope.pastResponses) {
    for (let i = 0; i < $scope.pastResponses.length; i++) {
      let response = $scope.pastResponses[i];
      $scope.responses["r" + response.item_id] = typeof response.response === 'object' ? response.response : {
        value: response.response,
        skipped: response.skipped
      };
    }
  }

  for (let i = 0; i < $scope.items.length; i++) {
    let item = $scope.items[i];
    if (typeof item.responseOptions === "string") {
      item.responseOptions = JSON.parse(item.responseOptions);
    }
    if (typeof $scope.responses["r" + item.id] === 'undefined') {
      $scope.responses["r" + item.id] = {
        skipped: 0
      };
    }
    
    $scope.responses["r" + item.id].isValid = function() {
      return typeof this.value !== 'undefined' && this.value !== null && this.value !== ""; 
    }
    
    testRunner.addExtraControl("skip"+item.id, function() {
      return $scope.responses["r"+item.id].skipped;
    });
  }
});
