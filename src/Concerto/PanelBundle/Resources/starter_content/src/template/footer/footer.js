testRunner.controllerProvider.register("footer", function($scope) {
  $scope.footerContent = testRunner.R.footerContent ? testRunner.R.footerContent : 'Created with <a href="http://www.concertoplatform.com" target="_blank">Concerto Platform</a>';
});
