'use strict';

testRunner.controller('testRunnerController', [
    '$scope', '$uibModal', '$compile', 'sessionResume',
    function ($scope, $uibModal, $compile, sessionResume) {
        $scope.concertoOptions = {};
        var RESPONSE_VIEW_TEMPLATE = 0;
        var RESPONSE_FINISHED = 1;
        var RESPONSE_SUBMIT = 2;
        var RESPONSE_SERIALIZE = 3;
        var RESPONSE_SERIALIZATION_FINISHED = 4;
        var RESPONSE_VIEW_FINAL_TEMPLATE = 5;
        var RESPONSE_VIEW_RESUME = 6;
        var RESPONSE_RESULTS = 7;
        var RESPONSE_AUTHENTICATION_FAILED = 8;
        var RESPONSE_STARTING = 9;
        var RESPONSE_KEEPALIVE_CHECKIN = 10;
        var RESPONSE_UNRESUMABLE = 11;
        var RESPONSE_ERROR = -1;
        $scope.init = function (node, directory, test, params, debug, keepAliveInterval) {
            var callback = function (response, hash) {
                testRunner.overridableCallback(response);
                switch (response.code) {
                    case RESPONSE_STARTING:
                        var session = sessionResume.getSessionObject($scope.concertoOptions.testId);
                        if (session !== null) {
                            $scope.initializeResumeDialog(session);
                            return false;
                        }
                        return true;
                    case RESPONSE_VIEW_TEMPLATE:
                        if (response.isResumable) {
                            sessionResume.saveSessionCookie(response.hash, $scope.concertoOptions.testId);
                        }
                        return true;
                    case RESPONSE_VIEW_FINAL_TEMPLATE:
                    case RESPONSE_FINISHED:
                    case RESPONSE_ERROR:
                    case RESPONSE_AUTHENTICATION_FAILED:
                    case RESPONSE_UNRESUMABLE:
                        sessionResume.removeSessionCookie(hash);
                        return true;
                }
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

        $scope.initializeResumeDialog = function (lastSession) {
            var modalInstance = $uibModal.open({
                templateUrl: 'session_resume_dialog.html',
                controller: sessionResumeController,
                size: "sm"
            });
            modalInstance.result.then(function (response) {
                switch (response) {
                    case 0: //start new
                        $scope.startTest(lastSession.hash);
                        break;
                    case 1: //resume
                        $scope.resumeTest(lastSession.hash);
                        break;
                }
            });
        };

        $scope.startTest = function (hash) {
            sessionResume.removeSessionCookie(hash);
            var testElement = angular.element("<concerto-test concerto-options='concertoOptions' />");
            angular.element("#testContainer").html(testElement);
            $compile(testElement)($scope);
        };

        $scope.resumeTest = function (hash) {
            $scope.concertoOptions.hash = hash;
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