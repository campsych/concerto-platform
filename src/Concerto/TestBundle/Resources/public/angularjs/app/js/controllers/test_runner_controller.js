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