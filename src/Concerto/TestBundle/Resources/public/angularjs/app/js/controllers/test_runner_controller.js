'use strict';

testRunner.controller('testRunnerController', [
  '$scope', '$compile',
  function ($scope, $compile) {

    $scope.concertoOptions = {};
    $scope.init = function (directory, testSlug, testName, params, debug, keepAliveInterval) {

      $scope.concertoOptions = angular.extend($scope.concertoOptions, {
        directory: directory,
        testSlug: testSlug,
        testName: testName,
        params: params,
        debug: debug,
        keepAliveInterval: keepAliveInterval
      });
    };

    $scope.startTest = function (hash) {
      var testElement = angular.element("<concerto-test concerto-options='concertoOptions' />");
      angular.element("#testContainer").html(testElement);
      $compile(testElement)($scope);
    };

    $scope.startTest();
  }
]);

testRunner.loadScripts = function (urls) {
  urls.forEach(function (src) {
    if ($("script[src='" + src + "']").length > 0) return;
    var script = document.createElement('script');
    script.type = "text/javascript";
    script.async = false;
    script.src = src;
    document.head.appendChild(script);
  });
};

testRunner.onSubmitView = function(values) {};