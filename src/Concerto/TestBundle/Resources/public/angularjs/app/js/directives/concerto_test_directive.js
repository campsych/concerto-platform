'use strict';
testRunner.settings = {
    finishedHtml: null,
    sessionLimitReachedHtml: null,
    testNotFoundHtml: null,
    sessionLostHtml: null,
    connectionRetryHtml: null,
    loaderHtml: null,
    testErrorHtml: null,
    serverErrorHtml: null,
    clientErrorHtml: null,
    timeFormat: "HH:mm:ss"
};

testRunner.directive('concertoTest', ['$http', '$interval', '$timeout', '$sce', '$compile', '$templateCache', 'dateFilter', 'FileUploader', '$window',
    function ($http, $interval, $timeout, $sce, $compile, $templateCache, dateFilter, FileUploader, $window) {
        function link(scope, element, attrs) {

            if (testRunner.settings.finishedHtml === null) testRunner.settings.finishedHtml = $templateCache.get("finished_template.html");
            if (testRunner.settings.testErrorHtml === null) testRunner.settings.testErrorHtml = $templateCache.get("test_error_template.html");
            if (testRunner.settings.serverErrorHtml === null) testRunner.settings.serverErrorHtml = $templateCache.get("server_error_template.html");
            if (testRunner.settings.clientErrorHtml === null) testRunner.settings.clientErrorHtml = $templateCache.get("client_error_template.html");
            if (testRunner.settings.sessionLimitReachedHtml === null) testRunner.settings.sessionLimitReachedHtml = $templateCache.get("session_limit_reached_template.html");
            if (testRunner.settings.testNotFoundHtml === null) testRunner.settings.testNotFoundHtml = $templateCache.get("test_not_found_template.html");
            if (testRunner.settings.sessionLostHtml === null) testRunner.settings.sessionLostHtml = $templateCache.get("session_lost_template.html");
            if (testRunner.settings.connectionRetryHtml === null) testRunner.settings.connectionRetryHtml = $templateCache.get("connection_retry_template.html");
            if (testRunner.settings.loaderHtml === null) testRunner.settings.loaderHtml = $templateCache.get("loading_template.html");

            var DISPLAY_UNKNOWN = -1;
            var DISPLAY_LOADER_SHOWING = 0;
            var DISPLAY_LOADER_SHOWN = 1;
            var DISPLAY_LOADER_HIDING = 2;
            var DISPLAY_LOADER_HIDDEN = 3;
            var DISPLAY_VIEW_SHOWING = 4;
            var DISPLAY_VIEW_SHOWN = 5;
            var DISPLAY_VIEW_HIDING = 6;
            var DISPLAY_VIEW_HIDDEN = 7;
            var RESPONSE_VIEW_TEMPLATE = 0;
            var RESPONSE_FINISHED = 1;
            var RESPONSE_SUBMIT = 2;
            var RESPONSE_VIEW_FINAL_TEMPLATE = 5;
            var RESPONSE_AUTHENTICATION_FAILED = 8;
            var RESPONSE_STARTING = 9;
            var RESPONSE_KEEPALIVE_CHECKIN = 10;
            var RESPONSE_SESSION_LIMIT_REACHED = 12;
            var RESPONSE_TEST_NOT_FOUND = 13;
            var RESPONSE_SESSION_LOST = 14;
            var RESPONSE_WORKER = 15;
            var RESPONSE_ERROR = -1;
            var SOURCE_PANEL_NODE = 0;
            var SOURCE_PROCESS = 1;
            var SOURCE_TEST_NODE = 2;
            var internalSettings = angular.extend({
                debug: false,
                params: null,
                platformUrl: "",
                appUrl: "",
                testSlug: null,
                testName: null,
                keepAliveInterval: 0
            }, scope.options);
            testRunner.settings.platformUrl = internalSettings.platformUrl;
            testRunner.settings.appUrl = internalSettings.appUrl;
            testRunner.sessionHash = null;
            var timeLimit = 0;
            var timer = 0;
            var timerId;
            var retryTimeTotal = 15;
            var retryTimer = 0;
            var retryTimerId;
            var keepAliveTimerPromise;
            var displayState = DISPLAY_UNKNOWN;
            var isViewReady = false;
            var lastResponseTime = 0;
            var lastResponse = null;
            scope.timeLeft = "";
            scope.timerStarted = null;
            scope.retryTimeLeft = "";
            scope.retryTimeStarted = null;

            scope.html = testRunner.settings.loaderHtml;
            scope.fileUploader = new FileUploader();
            scope.fileUploader.removeAfterUpload = true;
            scope.R = {};

            scope.$watch('html', function (newValue) {
                try {
                    angular.element("#testHtml").empty().append(newValue);
                    $compile(element.contents())(scope);
                } catch (e) {
                    scope.logClientSideError(e.toString());
                }

                if (displayState === DISPLAY_VIEW_SHOWN && lastResponse != null && lastResponse.code === RESPONSE_VIEW_TEMPLATE) {
                    initializeTimer();
                    startKeepAlive(lastResponse);
                    addSubmitEvents();
                }
            });

            scope.logClientSideError = function (error) {
                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/log", {
                    error: error
                });
                console.error(error);
            };

            function joinHtml(css, js, html) {
                if (js != null)
                    html = html + "<script>" + js + "</script>";
                if (css != null)
                    html = "<style>" + css + "</style>" + html;
                return html;
            }

            function clearTimer() {
                $interval.cancel(timerId);
                $interval.cancel(keepAliveTimerPromise);
                $interval.cancel(retryTimerId);
            }

            function initializeTimer() {
                if (timeLimit > 0) {
                    scope.timerStarted = new Date();
                    timer = timeLimit;
                    scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), testRunner.settings.timeFormat);
                    timerId = $interval(function () {
                        timeTick();
                    }, 1000);
                } else {
                    scope.timeLeft = "";
                }
            }

            function startKeepAlive(lastResponse) {
                if (internalSettings.keepAliveInterval > 0) {
                    keepAliveTimerPromise = $interval(function () {
                        $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/keepalive", {}).then(function (httpResponse) {
                            if (displayState !== DISPLAY_VIEW_SHOWN || lastResponse == null || lastResponse.code !== RESPONSE_VIEW_TEMPLATE || httpResponse.data.code !== RESPONSE_KEEPALIVE_CHECKIN)
                                $interval.cancel(keepAliveTimerPromise);
                        });
                    }, internalSettings.keepAliveInterval * 1000);
                }
            }

            function timeTick() {
                if (timer > 0) {
                    timer = Math.round(timeLimit - ((new Date()).getTime() - scope.timerStarted.getTime()) / 1000);
                    scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), testRunner.settings.timeFormat);
                    if (timer <= 0) {
                        scope.submitView("timeout", true);
                    }
                }
            }

            function startNewTest() {
                showLoader();
                let path = "";
                if (internalSettings.debug) {
                    if (internalSettings.existingSessionHash !== null) {
                        path = internalSettings.appUrl + "/admin/test/session/" + internalSettings.existingSessionHash + "/resume/debug";
                    } else {
                        path = internalSettings.appUrl + "/admin/test/" + internalSettings.testSlug + "/start_session/debug/" + encodeURIComponent(internalSettings.params);
                    }
                } else {
                    if (internalSettings.existingSessionHash !== null) {
                        path = internalSettings.appUrl + "/test/session/" + internalSettings.existingSessionHash + "/resume";
                    } else {
                        if (internalSettings.testName !== null) {
                            path = internalSettings.appUrl + "/test_n/" + internalSettings.testName + "/start_session/" + encodeURIComponent(internalSettings.params);
                        } else {
                            path = internalSettings.appUrl + "/test/" + internalSettings.testSlug + "/start_session/" + encodeURIComponent(internalSettings.params);
                        }
                    }
                }

                $http.post(path, {}).then(
                    function success(httpResponse) {
                        if (internalSettings.debug && httpResponse.data.debug)
                            console.log(httpResponse.data.debug);
                        lastResponse = httpResponse.data;
                        lastResponseTime = new Date();
                        isViewReady = true;

                        switch (lastResponse.code) {
                            case RESPONSE_VIEW_TEMPLATE:
                            case RESPONSE_VIEW_FINAL_TEMPLATE: {
                                testRunner.sessionHash = httpResponse.data.hash;
                                timeLimit = httpResponse.data.timeLimit;
                                updateLoader(httpResponse.data.data);
                                break;
                            }
                        }

                        showView();
                    },
                    function error(httpResponse) {
                        if (httpResponse.status >= 500 && httpResponse.status < 600) {
                            isViewReady = true;
                            showView(testRunner.settings.serverErrorHtml);
                        } else if (httpResponse.status >= 400 && httpResponse.status < 500) {
                            isViewReady = true;
                            showView(testRunner.settings.clientErrorHtml);
                        }
                    }
                );
            }

            function updateLoader(data) {
                var loaderHead = data.loaderHead ? data.loaderHead.trim() : null;
                var loaderCss = data.loaderCss ? data.loaderCss.trim() : null;
                var loaderJs = data.loaderJs ? data.loaderJs.trim() : null;
                var loaderHtml = data.loaderHtml ? data.loaderHtml.trim() : null;
                if (loaderCss || loaderJs || loaderHtml) {
                    testRunner.settings.loaderHtml = joinHtml(loaderCss, loaderJs, loaderHtml);
                }
                if (loaderHead) {
                    internalSettings.loaderHead = loaderHead;
                }
            }

            scope.runWorker = function (name, passedVals, successCallback, errorCallback) {
                var values = getControlsValues();
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                values["bgWorker"] = name;

                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/worker", {
                    values: values
                }).then(
                    function success(httpResponse) {
                        if (internalSettings.debug && httpResponse.data.debug)
                            console.log(httpResponse.data.debug);

                        if (successCallback != null) {
                            successCallback.call(this, httpResponse.data.data);
                        }
                    },
                    function error(httpResult) {
                        if (errorCallback != null) {
                            errorCallback.call(this, httpResult.data, httpResult.status);
                        }
                    }
                );
            };

            scope.submitView = function (btnName, isTimeout, passedVals) {
                if (displayState !== DISPLAY_VIEW_SHOWN) {
                    return;
                }

                var eventBeforeSubmitView = new CustomEvent('beforeSubmitView', {
                    detail: {
                        buttonPressed: btnName
                    }
                });
                $window.dispatchEvent(eventBeforeSubmitView);

                removeSubmitEvents();
                clearTimer();
                var values = getControlsValues();
                hideView();

                var eventSubmitView = new CustomEvent('submitView', {
                    detail: {
                        buttonPressed: btnName,
                        values: values
                    }
                });
                $window.dispatchEvent(eventSubmitView);

                if (scope.fileUploader.queue.length > 0) {
                    scope.fileUploader.onCompleteAll = function () {
                        submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                    };
                    scope.fileUploader.onSuccessItem = function (item, response, status, headers) {
                        if (response.result == 0) {
                            addPairToValues(values, response.name, response.file_path);
                        }
                    };
                    scope.fileUploader.uploadAll();
                } else {
                    submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                }
            };

            function submitViewPostValueGetter(btnName, isTimeout, passedVals, values) {
                values["buttonPressed"] = btnName ? btnName : "";
                values["isTimeout"] = isTimeout ? 1 : 0;
                values["retryTimeTaken"] = scope.retryTimeStarted === null ? 0 : (((new Date()).getTime() - scope.retryTimeStarted.getTime()) / 1000);
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/submit", {
                    values: values
                }).then(
                    function success(httpResponse) {
                        var eventSubmitViewResponseSuccess = new CustomEvent('submitViewResponseSuccess', {
                            detail: {
                                response: httpResponse.data
                            }
                        });
                        $window.dispatchEvent(eventSubmitViewResponseSuccess);

                        scope.retryTimeStarted = null;

                        if (internalSettings.debug && httpResponse.data.debug)
                            console.log(httpResponse.data.debug);
                        lastResponse = httpResponse.data;
                        lastResponseTime = new Date();
                        switch (lastResponse.code) {
                            case RESPONSE_VIEW_TEMPLATE:
                            case RESPONSE_VIEW_FINAL_TEMPLATE: {
                                testRunner.sessionHash = httpResponse.data.hash;
                                timeLimit = httpResponse.data.timeLimit;
                                updateLoader(httpResponse.data.data);
                                break;
                            }
                        }

                        isViewReady = true;
                        showView();
                    },
                    function error(httpResponse) {
                        var eventSubmitViewResponseError = new CustomEvent('submitViewResponseError', {
                            detail: {
                                error: httpResponse.data,
                                status: httpResponse.status
                            }
                        });
                        $window.dispatchEvent(eventSubmitViewResponseError);

                        if (httpResponse.status === -1) {
                            showConnectionProblems(btnName, isTimeout, passedVals, values);
                        } else if (httpResponse.status >= 500 && httpResponse.status < 600) {
                            scope.retryTimeStarted = null;

                            isViewReady = true;
                            showView(testRunner.settings.serverErrorHtml);
                        } else if (httpResponse.status >= 400 && httpResponse.status < 500) {
                            scope.retryTimeStarted = null;

                            isViewReady = true;
                            showView(testRunner.settings.clientErrorHtml);
                        }
                    }
                );
            }

            function showConnectionProblems(btnName, isTimeout, passedVals, values) {
                if (scope.retryTimeStarted === null) scope.retryTimeStarted = new Date();
                initializeRetryTimer(btnName, isTimeout, passedVals, values);
                scope.html = testRunner.settings.connectionRetryHtml;
            }

            function initializeRetryTimer(btnName, isTimeout, passedVals, values) {
                if (retryTimeTotal > 0) {
                    retryTimer = retryTimeTotal;
                    scope.retryTimeLeft = retryTimer;
                    retryTimerId = $interval(function () {
                        retryTimeTick(btnName, isTimeout, passedVals, values);
                    }, 1000);
                } else {
                    scope.retryTimeLeft = "";
                }
            }

            function retryTimeTick(btnName, isTimeout, passedVals, values) {
                if (retryTimer > 0) {
                    retryTimer--;
                    scope.retryTimeLeft = retryTimer;
                    if (retryTimer === 0) {
                        clearTimer();
                        hideView();
                        submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                    }
                }
            }

            function showView(content) {
                if (displayState === DISPLAY_LOADER_SHOWN) {
                    hideLoader(content);
                }
                if (displayState === DISPLAY_VIEW_HIDDEN || displayState === DISPLAY_LOADER_HIDDEN) {

                    var head = null;
                    var css = "";
                    var js = "";
                    var html = (content != null) ? content : "";
                    clearExtraControlsValues();

                    if (content == null) {
                        switch (lastResponse.code) {
                            case RESPONSE_VIEW_TEMPLATE:
                            case RESPONSE_VIEW_FINAL_TEMPLATE:
                                css = lastResponse.data.templateCss ? lastResponse.data.templateCss.trim() : "";
                                js = lastResponse.data.templateJs ? lastResponse.data.templateJs.trim() : "";
                                html = lastResponse.data.templateHtml ? lastResponse.data.templateHtml.trim() : "";
                                head = lastResponse.data.templateHead ? lastResponse.data.templateHead.trim() : "";
                                break;
                            case RESPONSE_AUTHENTICATION_FAILED:
                            case RESPONSE_ERROR:
                                html = testRunner.settings.testErrorHtml;
                                break;
                            case RESPONSE_FINISHED:
                                html = testRunner.settings.finishedHtml;
                                break;
                            case RESPONSE_SESSION_LIMIT_REACHED:
                                html = testRunner.settings.sessionLimitReachedHtml;
                                break;
                            case RESPONSE_TEST_NOT_FOUND:
                                html = testRunner.settings.testNotFoundHtml;
                                break;
                            case RESPONSE_SESSION_LOST:
                                html = testRunner.settings.sessionLostHtml;
                                break;
                        }

                        if (typeof (lastResponse.data) !== 'undefined' && lastResponse.data.templateParams != null) {
                            scope.R = angular.fromJson(lastResponse.data.templateParams);
                            testRunner.R = scope.R;
                        }

                        if (head != null && head.trim() !== "") {
                            angular.element("head").append($compile(head)(scope));
                        }
                    }

                    displayState = DISPLAY_VIEW_SHOWN;
                    scope.html = joinHtml(css, js, html);
                }
            }

            function hideView() {
                isViewReady = false;
                displayState = DISPLAY_VIEW_HIDDEN;
                if (isViewReady) {
                    showView();
                } else {
                    showLoader();
                }
            }

            function showLoader() {
                displayState = DISPLAY_LOADER_SHOWN;

                if (internalSettings.loaderHead != null)
                    angular.element("head").append($compile(internalSettings.loaderHead)(scope));

                scope.html = testRunner.settings.loaderHtml;
            }

            function hideLoader(content) {
                displayState = DISPLAY_LOADER_HIDDEN;
                showView(content);
            }

            function getControlsValues() {
                var vars = {
                    timeTaken: ((new Date()).getTime() - lastResponseTime.getTime()) / 1000
                };
                element.find(
                    "input:text, " +
                    "input[type='color'], " +
                    "input[type='range'], " +
                    "input[type='file'], " +
                    "input[type='hidden'], " +
                    "input[type='email'], " +
                    "input[type='month'], " +
                    "input[type='number'], " +
                    "input[type='search'], " +
                    "input[type='tel'], " +
                    "input[type='time'], " +
                    "input[type='url'], " +
                    "input[type='week'], " +
                    "input:password, " +
                    "input[type='date'], " +
                    "input[type='datetime-local'], " +
                    "textarea, " +
                    "select, " +
                    "input:checkbox:checked, " +
                    "input:radio:checked"
                ).each(function () {
                    var name = $(this).attr("name");
                    if (name == null) return;
                    var value = $(this).val();
                    if ($(this).attr("type") == "file") {
                        if ($(this)[0].files.length == 0)
                            return;
                        var file = $(this)[0].files[0];
                        scope.queueUpload(name, file);
                        return;
                    }
                    addPairToValues(vars, name, value);
                });

                angular.merge(vars, getExtraControlsValues());

                return vars;
            }

            function addPairToValues(vars, name, value) {
                var found = false;
                for (var k in vars) {
                    if (k === name) {
                        found = true;
                        if (vars[k] instanceof Array)
                            vars[k].push(value);
                        else
                            vars[k] = [vars[k], value];
                    }
                }

                if (!found) {
                    vars[name] = value;
                }
                return vars;
            }

            function addSubmitEvents() {
                element.find(":button:not(.concerto-nosubmit)").bind("click", function (event) {
                    scope.submitView($(this).attr("name"), false);
                });
                element.find("input:image:not(.concerto-nosubmit)").bind("click", function (event) {
                    scope.submitView($(this).attr("name"), false);
                });
                element.find("input:submit:not(.concerto-nosubmit)").bind("click", function (event) {
                    scope.submitView($(this).attr("name"), false);
                });
            }

            function removeSubmitEvents() {
                element.find(":button:not(.concerto-nosubmit)").unbind("click");
                element.find("input:image:not(.concerto-nosubmit)").unbind("click");
                element.find("input:submit:not(.concerto-nosubmit)").unbind("click");
            }

            var extraControls = {};

            scope.addExtraControl = function (name, getter) {
                extraControls[name] = getter;
            };

            function getExtraControlsValues() {
                var vals = {};
                for (var name in extraControls) {
                    var val = extraControls[name]();
                    if (val !== null) {
                        vals[name] = val;
                    }
                }
                return vals;
            }

            function clearExtraControlsValues() {
                extraControls = {};
            }

            scope.addEventListener = function (name, callback) {
                $window.addEventListener(name, callback);
            };
            scope.removeEventListener = function (name, callback) {
                $window.removeEventListener(name, callback);
            };
            scope.queueUpload = function (name, file) {
                scope.fileUploader.url = internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/upload";
                scope.fileUploader.formData = [{
                    name: name
                }];
                if (typeof (file) !== 'File') {
                    file = new File([file], name);
                }
                scope.fileUploader.addToQueue(file);
            };

            testRunner.submitView = scope.submitView;
            testRunner.runWorker = scope.runWorker;
            testRunner.logClientSideError = scope.logClientSideError;
            testRunner.addExtraControl = scope.addExtraControl;
            testRunner.addEventListener = scope.addEventListener;
            testRunner.removeEventListener = scope.removeEventListener;
            testRunner.queueUpload = scope.queueUpload;

            var options = scope.options;
            if (options.testSlug != null || options.testName != null) {
                startNewTest();
                return;
            }
        }

        return {
            restrict: "E",
            templateUrl: "test_container.html",
            replace: true,
            scope: {
                options: "=concertoOptions"
            },
            link: link
        };
    }
]);
