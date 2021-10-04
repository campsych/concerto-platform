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
                admin: false,
                params: null,
                platformUrl: "",
                appUrl: "",
                testSlug: null,
                testName: null,
                keepAliveInterval: 0,
                keepAliveTolerance: 0
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
            var lastSuccessfulKeepAliveTime;
            var displayState = DISPLAY_UNKNOWN;
            var isViewReady = false;
            var lastResponseTime = 0;
            var lastResponse = null;
            var stopped = false;
            var submitId = 0;
            var cientSideErrorLoggedNum = 0;
            scope.timeLeft = "";
            scope.timerStarted = null;
            scope.retryTimeLeft = "";
            scope.retryTimeStarted = null;

            scope.html = testRunner.settings.loaderHtml;
            scope.fileUploader = new FileUploader();
            scope.fileUploader.removeAfterUpload = true;
            scope.R = {};

            $window.addEventListener("error", function (event) {
                if(cientSideErrorLoggedNum === 0) {
                    scope.logClientSideError(event.message);
                }
            });

            scope.$watch('html', function (newValue) {
                angular.element("#testHtml").empty().append(newValue);
                $compile(element.contents())(scope);

                if (!stopped && displayState === DISPLAY_VIEW_SHOWN && lastResponse != null && lastResponse.code === RESPONSE_VIEW_TEMPLATE) {
                    startTestTimer();
                    startKeepAlive();
                    addSubmitEvents();
                }
            });

            scope.logClientSideError = function (error) {
                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/log", {
                    error: error,
                    token: getToken()
                });
                cientSideErrorLoggedNum++;
            };

            function joinHtml(css, js, html) {
                if (js != null)
                    html = html + "<script>" + js + "</script>";
                if (css != null)
                    html = "<style>" + css + "</style>" + html;
                return html;
            }

            function clearAllTimers() {
                stopTestTimer();
                $interval.cancel(keepAliveTimerPromise);
                $interval.cancel(retryTimerId);
            }

            function stopTestTimer() {
                $interval.cancel(timerId);
            }

            function startTestTimer(limit = null) {
                if (limit === null) limit = timeLimit;
                if (limit > 0) {
                    scope.timerStarted = new Date();
                    timer = limit;
                    scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), testRunner.settings.timeFormat);
                    timerId = $interval(function () {
                        timeTick();
                    }, 1000);
                } else {
                    scope.timeLeft = "";
                }
            }

            function startKeepAlive() {
                if (internalSettings.keepAliveInterval > 0) {
                    if (lastSuccessfulKeepAliveTime === null) lastSuccessfulKeepAliveTime = new Date();
                    keepAliveTimerPromise = $interval(function () {
                        $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/keepalive", {
                            token: getToken()
                        }).then(
                            function success(httpResponse) {
                                if (httpResponse.data.code === RESPONSE_KEEPALIVE_CHECKIN) {
                                    lastSuccessfulKeepAliveTime = new Date();
                                    setToken(httpResponse.data.token);
                                } else {
                                    removeSubmitEvents();
                                    clearAllTimers();
                                    hideView();

                                    showView(httpResponse.data);
                                }
                            },
                            function error(httpResponse) {
                                let stop = httpResponse.status >= 400;
                                if (!stop && internalSettings.keepAliveTolerance > 0) {
                                    let lastSuccessfulCheckinAgo = (new Date().getTime() - lastSuccessfulKeepAliveTime.getTime()) / 1000;
                                    if (lastSuccessfulCheckinAgo > internalSettings.keepAliveTolerance) stop = true;
                                }
                                if (stop) {
                                    removeSubmitEvents();
                                    clearAllTimers();
                                    hideView();

                                    handleHttpError(httpResponse);
                                }
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
                let params = {};
                if (internalSettings.debug) {
                    path = internalSettings.appUrl + "/admin/test/" + internalSettings.testSlug + "/start_session/debug/" + encodeURIComponent(internalSettings.params);
                } else {
                    if (internalSettings.existingSessionHash !== null) {
                        path = internalSettings.appUrl + "/test/session/" + internalSettings.existingSessionHash + "/resume";
                        params.token = getToken();
                    } else {
                        if (internalSettings.testName !== null) {
                            path = internalSettings.appUrl + (internalSettings.admin ? "/admin" : "") + "/test_n/" + internalSettings.testName + "/start_session/" + encodeURIComponent(internalSettings.params);
                        } else {
                            path = internalSettings.appUrl + (internalSettings.admin ? "/admin" : "") + "/test/" + internalSettings.testSlug + "/start_session/" + encodeURIComponent(internalSettings.params);
                        }
                    }
                }

                $http.post(path, params).then(
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
                                setToken(httpResponse.data.token);
                                timeLimit = httpResponse.data.timeLimit;
                                if(httpResponse.data.data.lastSubmitId) submitId = httpResponse.data.data.lastSubmitId;
                                updateLoader(httpResponse.data.data);
                                break;
                            }
                        }

                        showView();
                    },
                    function error(httpResponse) {
                        handleHttpError(httpResponse);
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

            scope.runWorker = function (name, passedVals, successCallback, stopOnNetworkError) {
                if (typeof (stopOnNetworkError) === 'undefined') stopOnNetworkError = false;
                var values = getControlsValues();
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                values["bgWorker"] = name;

                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/worker", {
                    token: getToken(),
                    values: values
                }).then(
                    function success(httpResponse) {
                        if (internalSettings.debug && httpResponse.data.debug)
                            console.log(httpResponse.data.debug);

                        if (successCallback != null) {
                            successCallback.call(this, httpResponse.data.data);
                        }

                        if (httpResponse.data.code === RESPONSE_WORKER) {
                            setToken(httpResponse.data.token);
                        } else {
                            removeSubmitEvents();
                            clearAllTimers();
                            hideView();

                            showView(httpResponse.data);
                        }
                    },
                    function error(httpResult) {
                        let stop = httpResult.status >= 400;
                        if (stop || stopOnNetworkError) {
                            removeSubmitEvents();
                            clearAllTimers();
                            hideView();

                            handleHttpError(httpResult);
                        }
                    }
                );
            };

            scope.submitView = function (btnName, isTimeout, passedVals) {
                submitId++;
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
                clearAllTimers();
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
                            setToken(response.token);
                            addPairToValues(values, response.name, response.file_path);
                        }
                    };
                    scope.fileUploader.uploadAll();
                } else {
                    submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                }
            };

            function handleHttpError(httpResponse) {
                stopped = true;
                if (httpResponse.status === 403) scope.html = testRunner.settings.sessionLostHtml;
                else if (httpResponse.status >= 500) scope.html = testRunner.settings.serverErrorHtml;
                else scope.html = testRunner.settings.clientErrorHtml;
            }

            function submitViewPostValueGetter(btnName, isTimeout, passedVals, values) {
                values["buttonPressed"] = btnName ? btnName : "";
                values["isTimeout"] = isTimeout ? 1 : 0;
                values["submitId"] = submitId;
                values["retryTimeTaken"] = scope.retryTimeStarted === null ? 0 : (((new Date()).getTime() - scope.retryTimeStarted.getTime()) / 1000);
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                $http.post(internalSettings.appUrl + "/test/session/" + testRunner.sessionHash + "/submit", {
                    token: getToken(),
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
                                setToken(httpResponse.data.token);
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
                        } else handleHttpError(httpResponse);
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
                        clearAllTimers();
                        hideView();
                        submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                    }
                }
            }

            function showView(response) {
                if (displayState === DISPLAY_LOADER_SHOWN) displayState = DISPLAY_LOADER_HIDDEN;
                if (displayState === DISPLAY_VIEW_HIDDEN || displayState === DISPLAY_LOADER_HIDDEN) {

                    var head = null;
                    var css = "";
                    var js = "";
                    var html = "";
                    clearExtraControlsValues();

                    if (response == null) response = lastResponse;
                    switch (response.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                            css = response.data.templateCss ? response.data.templateCss.trim() : "";
                            js = response.data.templateJs ? response.data.templateJs.trim() : "";
                            html = response.data.templateHtml ? response.data.templateHtml.trim() : "";
                            head = response.data.templateHead ? response.data.templateHead.trim() : "";
                            break;
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                            stopped = true;
                            css = response.data.templateCss ? response.data.templateCss.trim() : "";
                            js = response.data.templateJs ? response.data.templateJs.trim() : "";
                            html = response.data.templateHtml ? response.data.templateHtml.trim() : "";
                            head = response.data.templateHead ? response.data.templateHead.trim() : "";
                            break;
                        case RESPONSE_AUTHENTICATION_FAILED:
                        case RESPONSE_ERROR:
                            stopped = true;
                            html = testRunner.settings.testErrorHtml;
                            break;
                        case RESPONSE_FINISHED:
                            stopped = true;
                            html = testRunner.settings.finishedHtml;
                            break;
                        case RESPONSE_SESSION_LIMIT_REACHED:
                            stopped = true;
                            html = testRunner.settings.sessionLimitReachedHtml;
                            break;
                        case RESPONSE_TEST_NOT_FOUND:
                            stopped = true;
                            html = testRunner.settings.testNotFoundHtml;
                            break;
                        case RESPONSE_SESSION_LOST:
                            stopped = true;
                            html = testRunner.settings.sessionLostHtml;
                            break;
                    }
                    if(response.data && response.data.templateHtmlOverride) html = response.data.templateHtmlOverride;
                    if(response.data && response.data.templateCssOverride) css = response.data.templateCssOverride;
                    if(response.data && response.data.templateJsOverride) js = response.data.templateJsOverride;
                    if(response.data && response.data.templateHeadOverride) head = response.data.templateHeadOverride;

                    if (typeof (response.data) !== 'undefined' && response.data.templateParams != null) {
                        scope.R = angular.fromJson(response.data.templateParams);
                        testRunner.R = scope.R;
                    }

                    if (head != null && head.trim() !== "") {
                        angular.element("head").append($compile(head)(scope));
                    }
                }

                displayState = DISPLAY_VIEW_SHOWN;
                scope.html = joinHtml(css, js, html);
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
                    name: name,
                    token: getToken()
                }];
                if (typeof (file) !== 'File') {
                    file = new File([file], name);
                }
                scope.fileUploader.addToQueue(file);
            };

            function getToken() {
                return sessionStorage.getItem("concertoToken");
            }

            function setToken(token) {
                sessionStorage.setItem("concertoToken", token);
            }

            testRunner.submitView = scope.submitView;
            testRunner.runWorker = scope.runWorker;
            testRunner.logClientSideError = scope.logClientSideError;
            testRunner.addExtraControl = scope.addExtraControl;
            testRunner.addEventListener = scope.addEventListener;
            testRunner.removeEventListener = scope.removeEventListener;
            testRunner.queueUpload = scope.queueUpload;
            testRunner.startTestTimer = startTestTimer;
            testRunner.stopTestTimer = stopTestTimer;
            testRunner.getToken = getToken;
            testRunner.getControlValues = getControlsValues;

            var options = scope.options;
            if (options.testSlug != null || options.testName != null) {
                startNewTest();
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
