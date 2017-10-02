'use strict';

testRunner.controller('testRunnerController', [
    '$scope', '$compile',
    function ($scope, $compile) {
        $scope.concertoOptions = {};
        var RESPONSE_VIEW_TEMPLATE = 0;
        var RESPONSE_FINISHED = 1;
        var RESPONSE_SUBMIT = 2;
        var RESPONSE_VIEW_FINAL_TEMPLATE = 5;
        var RESPONSE_RESULTS = 7;
        var RESPONSE_AUTHENTICATION_FAILED = 8;
        var RESPONSE_STARTING = 9;
        var RESPONSE_KEEPALIVE_CHECKIN = 10;
        var RESPONSE_UNRESUMABLE = 11;
        var RESPONSE_ERROR = -1;
        $scope.init = function (node, directory, test, params, debug, keepAliveInterval) {
            var callback = function (response, hash) {
                testRunner.overridableCallback(response);
                return true;
            };

            $scope.concertoOptions = angular.extend($scope.concertoOptions, {
                nodeId: node,
                directory: directory,
                testId: test,
                params: params,
                debug: debug,
                callback: callback,
                keepAliveInterval: keepAliveInterval
            });

            $scope.startTest();
        };

        $scope.startTest = function (hash) {
            var testElement = angular.element("<concerto-test concerto-options='concertoOptions' />");
            angular.element("#testContainer").html(testElement);
            $compile(testElement)($scope);
        };
    }
]);

testRunner.overridableCallback = function (response) {
};

testRunner.submitView = function (buttonName, timeout, passedValues) {

};

testRunner.loadScripts = function(urls) {
    urls.forEach(function(src) {
        if($("script[src='"+src+"']").length > 0) return;
        var script = document.createElement('script');
        script.type = "text/javascript";
        script.async = false;
        script.src = src;
        document.head.appendChild(script);
    });
};