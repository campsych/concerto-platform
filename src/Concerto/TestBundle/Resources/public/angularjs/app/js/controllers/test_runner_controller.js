'use strict';

testRunner.controller('testRunnerController', [
    '$scope', '$compile',
    function ($scope, $compile) {

        $scope.concertoOptions = {};
        $scope.init = function (platformUrl, testSlug, testName, params, debug, keepAliveInterval, existingSessionHash) {

            $scope.concertoOptions = angular.extend($scope.concertoOptions, {
                platformUrl: platformUrl,
                testSlug: testSlug,
                testName: testName,
                params: params,
                debug: debug,
                keepAliveInterval: keepAliveInterval,
                existingSessionHash: existingSessionHash
            });
        };

        $scope.startTest = function (hash) {
            let testElement = angular.element("<concerto-test concerto-options='concertoOptions' />");
            angular.element("#testContainer").html(testElement);
            $compile(testElement)($scope);
        };

        $scope.startTest();
    }
]);