'use strict';

testRunner.controller('testRunnerController', [
    '$scope', '$compile',
    function ($scope, $compile) {

        $scope.concertoOptions = {};
        $scope.init = function (platform_url, appUrl, testSlug, testName, params, debug, admin, keepAliveInterval, keepAliveTolerance, existingSessionHash) {

            $scope.concertoOptions = angular.extend($scope.concertoOptions, {
                platformUrl: platform_url,
                appUrl: appUrl,
                testSlug: testSlug,
                testName: testName,
                params: params,
                debug: debug,
                admin: admin,
                keepAliveInterval: keepAliveInterval,
                keepAliveTolerance: keepAliveTolerance,
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