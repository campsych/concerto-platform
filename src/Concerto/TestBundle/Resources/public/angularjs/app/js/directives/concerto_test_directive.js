'use strict';

testRunner.directive('concertoTest', ['$http', '$interval', '$timeout', '$sce', '$compile', '$templateCache', 'dateFilter',
    function ($http, $interval, $timeout, $sce, $compile, $templateCache, dateFilter) {
        function link(scope, element, attrs) {

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
            var SOURCE_TEST_SERVER = 0;
            var SOURCE_PROCESS = 1;
            var SOURCE_R_SERVER = 2;
            var settings = angular.extend({
                debug: false,
                clientDebug: true,
                params: null,
                directory: "/",
                nodeId: null,
                testId: null,
                hash: null,
                unresumableHtml: $templateCache.get("unresumable_template.html"),
                finishedHtml: $templateCache.get("finished_template.html"),
                errorHtml: $templateCache.get("error_template.html"),
                loaderHead: "",
                loaderHtml: $templateCache.get("loading_template.html"),
                timeFormat: "HH:mm:ss",
                callback: null,
                keepAliveInterval: 0
            }, scope.options);
            var results = {};
            var timeLimit = 0;
            var timer = 0;
            var timerId;
            var keepAliveTimerPromise;
            var displayState = DISPLAY_UNKNOWN;
            var isViewReady = false;
            var lastResponseTime = 0;
            var isResumable = true;
            var lastResponse = null;
            scope.timeLeft = "";

            scope.html = settings.defaultLoaderHtml;
            scope.R = {};
            testRunner.R = {};

            scope.$watch('html', function (newValue) {
                angular.element("#testHtml").empty().append(newValue);
                $compile(element.contents())(scope);

                if (displayState === DISPLAY_VIEW_SHOWN && lastResponse != null && lastResponse.code === RESPONSE_VIEW_TEMPLATE) {
                    initializeTimer();
                    startKeepAlive(lastResponse);
                    addSubmitEvents();
                }
            });
            function clearTimer() {
                $interval.cancel(timerId);
                $interval.cancel(keepAliveTimerPromise);
            }

            function initializeTimer() {
                if (timeLimit > 0) {
                    timer = timeLimit;
                    scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), settings.timeFormat);
                    timerId = $interval(function () {
                        timeTick();
                    }, 1000);
                } else {
                    scope.timeLeft = "";
                }
            }

            function startKeepAlive(lastResponse) {
                if (settings.clientDebug)
                    console.log("start keep alive (" + settings.keepAliveInterval + ")");
                if (settings.keepAliveInterval > 0) {
                    keepAliveTimerPromise = $interval(function () {
                        $http.post(settings.directory + "test/session/" + lastResponse.hash + "/keepalive", {
                            node_id: settings.nodeId
                        }).success(function (response) {
                            if (settings.clientDebug)
                                console.log("keep-alive ping");
                            if (displayState !== DISPLAY_VIEW_SHOWN || lastResponse == null || lastResponse.code !== RESPONSE_VIEW_TEMPLATE)
                                $interval.cancel(keepAliveTimerPromise);
                        });
                    }, settings.keepAliveInterval * 1000);
                }
            }

            function timeTick() {
                if (timer > 0) {
                    timer--;
                    scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), settings.timeFormat);
                    if (timer === 0) {
                        scope.submitView("timeout", true);
                    }
                }
            }

            function startNewTest() {
                if (settings.clientDebug)
                    console.log("start");
                if (settings.callback != null) {
                    if (!settings.callback.call(this, {'source': SOURCE_TEST_SERVER, 'code': RESPONSE_STARTING}))
                        return;
                }
                showLoader();
                var path = "";
                if (settings.debug) {
                    path = settings.directory + "admin/TestSession/Test/" + settings.testId + "/debug/start/" + encodeURIComponent(settings.params);
                } else {
                    path = settings.directory + "test/" + settings.testId + "/session/start/" + encodeURIComponent(settings.params);
                }

                $http.post(path, {
                    node_id: settings.nodeId
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                        {
                            settings.hash = response.hash;
                            timeLimit = response.timeLimit;
                            isResumable = response.isResumable;
                            if (response.loaderHead != null && response.loaderHead != null) {
                                settings.loaderHead = response.loaderHead;
                                settings.loaderHtml = response.loaderHtml;
                            }
                            break;
                        }
                    }

                    isViewReady = true;
                    showView();
                    if (settings.callback != null) {
                        settings.callback.call(this, response, settings.hash);
                    }
                });
            }

            scope.submitView = function (btnName, isTimeout, passedVals) {
                if (displayState !== DISPLAY_VIEW_SHOWN) {
                    return;
                }

                if (settings.clientDebug)
                    console.log("submit");
                removeSubmitEvents()
                clearTimer();
                var values = getControlsValues();
                values["buttonPressed"] = btnName;
                values["isTimeout"] = isTimeout ? 1 : 0;
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                hideView();
                $http.post(settings.directory + "test/session/" + settings.hash + "/submit", {
                    node_id: settings.nodeId,
                    values: values
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                        {
                            settings.hash = response.hash;
                            timeLimit = response.timeLimit;
                            if (response.loaderHead != null && response.loaderHead != null) {
                                settings.loaderHead = response.loaderHead;
                                settings.loaderHtml = response.loaderHtml;
                            }
                            break;
                        }
                    }

                    isViewReady = true;
                    showView();
                    if (settings.callback != null) {
                        settings.callback.call(this, response, settings.hash);
                    }
                });
            }

            testRunner.submitView = scope.submitView;

            function resumeTest() {
                if (settings.clientDebug)
                    console.log("resume");
                showLoader();
                $http.post(settings.directory + "test/session/" + settings.hash + "/resume", {
                    node_id: settings.nodeId
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                        {
                            settings.hash = response.hash;
                            timeLimit = response.timeLimit;
                            if (response.loaderHead != null && response.loaderHead != null) {
                                settings.loaderHead = response.loaderHead;
                                settings.loaderHtml = response.loaderHtml;
                            }
                            break;
                        }
                    }

                    isViewReady = true;
                    showView();
                    if (settings.callback != null) {
                        settings.callback.call(this, response, settings.hash);
                    }
                });
            }

            function getResults() {
                if (settings.clientDebug)
                    console.log("result");
                $http.post(settings.directory + "test/session/" + settings.hash + "/results", {
                    node_id: settings.nodeId
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_RESULTS:
                        {
                            results = response.results;
                            break;
                        }
                    }

                    if (settings.callback != null) {
                        settings.callback.call(this, response.results, settings.hash);
                    }
                });
            }

            function showView() {
                if (settings.clientDebug)
                    console.log("showView (" + displayState + ")");
                if (displayState === DISPLAY_LOADER_SHOWN) {
                    hideLoader();
                }
                if (displayState === DISPLAY_VIEW_HIDDEN || displayState === DISPLAY_LOADER_HIDDEN) {

                    var head = null;
                    var html = null;
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                            html = lastResponse.templateHtml.trim();
                            head = lastResponse.templateHead.trim();
                            break;
                        case RESPONSE_AUTHENTICATION_FAILED:
                        case RESPONSE_ERROR:
                            html = settings.errorHtml;
                            break;
                        case RESPONSE_FINISHED:
                            html = settings.finishedHtml;
                            break;
                        case RESPONSE_UNRESUMABLE:
                            html = settings.unresumableHtml;
                            break;
                    }
                    displayState = DISPLAY_VIEW_SHOWN;

                    if (lastResponse.templateParams != null) {
                        scope.R = angular.extend(scope.R, angular.fromJson(lastResponse.templateParams));
                        testRunner.R = scope.R;
                    }

                    if (head != null && head !== "") {
                        angular.element("head").append($compile(head)(scope));
                    }
                    scope.html = html;
                }
            }

            function hideView() {
                if (settings.clientDebug)
                    console.log("hideView");
                isViewReady = false;
                displayState = DISPLAY_VIEW_HIDDEN;
                if (isViewReady) {
                    showView();
                } else {
                    showLoader();
                }
            }

            function showLoader() {
                if (settings.clientDebug)
                    console.log("showLoader");
                displayState = DISPLAY_LOADER_SHOWN;

                if (lastResponse.templateParams != null) {
                    scope.R = angular.extend(scope.R, angular.fromJson(lastResponse.templateParams));
                    testRunner.R = scope.R;
                }

                if (settings.loaderHead != null && settings.loaderHead.trim() !== "")
                    angular.element("head").append($compile(settings.loaderHead)(scope));

                scope.html = settings.loaderHtml;
            }

            function hideLoader() {
                if (settings.clientDebug)
                    console.log("hideLoader");
                displayState = DISPLAY_LOADER_HIDDEN;
                showView();
            }

            function getControlsValues() {
                var vars = {
                    timeTaken: ((new Date()).getTime() - lastResponseTime.getTime()) / 1000
                };
                element.find("input:text, input[type='range'], input[type='hidden'], input:password, textarea, select, input:checkbox:checked, input:radio:checked").each(function () {
                    var name = $(this).attr("name");
                    var value = $(this).val();
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
                });
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

            function  removeSubmitEvents() {
                element.find(":button:not(.concerto-nosubmit)").unbind("click");
                element.find("input:image:not(.concerto-nosubmit)").unbind("click");
                element.find("input:submit:not(.concerto-nosubmit)").unbind("click");
            }

            var options = scope.options;
            if (settings.clientDebug)
                console.log(options);
            if (options.hash != null) {
                resumeTest();
                return;
            }
            if (options.testId != null) {
                startNewTest();
                return;
            }
            if (settings.clientDebug)
                console.log("invalid options");
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
