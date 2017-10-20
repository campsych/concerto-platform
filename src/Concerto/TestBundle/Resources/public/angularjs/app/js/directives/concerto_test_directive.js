'use strict';

testRunner.directive('concertoTest', ['$http', '$interval', '$timeout', '$sce', '$compile', '$templateCache', 'dateFilter', 'FileUploader',
    function ($http, $interval, $timeout, $sce, $compile, $templateCache, dateFilter, FileUploader) {
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
            var RESPONSE_VIEW_FINAL_TEMPLATE = 5;
            var RESPONSE_RESULTS = 7;
            var RESPONSE_AUTHENTICATION_FAILED = 8;
            var RESPONSE_STARTING = 9;
            var RESPONSE_KEEPALIVE_CHECKIN = 10;
            var RESPONSE_UNRESUMABLE = 11;
            var RESPONSE_SESSION_LIMIT_REACHED = 12;
            var RESPONSE_TEST_NOT_FOUND = 13;
            var RESPONSE_ERROR = -1;
            var SOURCE_PANEL_NODE = 0;
            var SOURCE_PROCESS = 1;
            var SOURCE_TEST_NODE = 2;
            var settings = angular.extend({
                debug: false,
                clientDebug: false,
                params: null,
                directory: "/",
                nodeId: null,
                testId: null,
                hash: null,
                unresumableHtml: $templateCache.get("unresumable_template.html"),
                finishedHtml: $templateCache.get("finished_template.html"),
                errorHtml: $templateCache.get("error_template.html"),
                sessionLimitReachedHtml: $templateCache.get("session_limit_reached.html"),
                testNotFoundHtml: $templateCache.get("test_not_found.html"),
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
            var lastResponse = null;
            scope.timeLeft = "";

            scope.html = settings.loaderHtml;
            scope.fileUploader = new FileUploader();
            scope.fileUploader.removeAfterUpload = true;
            scope.R = {};
            testRunner.R = {};

            scope.$watch('html', function (newValue) {
                try {
                    angular.element("#testHtml").empty().append(newValue);
                    $compile(element.contents())(scope);
                } catch (e) {
                    $http.post(settings.directory + "test/session/" + lastResponse.hash + "/log", {
                        node_id: settings.nodeId,
                        error: e.toString()
                    });
                    console.error(e);
                }

                if (displayState === DISPLAY_VIEW_SHOWN && lastResponse != null && lastResponse.code === RESPONSE_VIEW_TEMPLATE) {
                    initializeTimer();
                    startKeepAlive(lastResponse);
                    addSubmitEvents();
                }
            });

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
                            if (displayState !== DISPLAY_VIEW_SHOWN || lastResponse == null || lastResponse.code !== RESPONSE_VIEW_TEMPLATE || response.code === RESPONSE_ERROR)
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
                    if (!settings.callback.call(this, {'source': SOURCE_PANEL_NODE, 'code': RESPONSE_STARTING}))
                        return;
                }
                showLoader();
                var path = "";
                if (settings.debug) {
                    path = settings.directory + "admin/test/" + settings.testId + "/session/start/debug/" + encodeURIComponent(settings.params);
                } else {
                    path = settings.directory + "test/" + settings.testId + "/session/start/" + encodeURIComponent(settings.params);
                }

                $http.post(path, {
                    node_id: settings.nodeId
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug && response.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    isViewReady = true;

                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE: {
                            settings.hash = response.hash;
                            timeLimit = response.timeLimit;
                            if (response.loaderHead.trim() != "" || response.loaderCss != "" || response.loaderJs != "" || response.loaderHtml != "")
                                settings.loaderHtml = joinHtml(response.loaderCss, response.loaderJs, response.loaderHtml);
                            break;
                        }
                    }

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
                hideView();
                if (scope.fileUploader.queue.length > 0) {
                    scope.fileUploader.onCompleteAll = function () {
                        submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                    }
                    scope.fileUploader.onSuccessItem = function (item, response, status, headers) {
                        if (response.result == 0) {
                            addPairToValues(values, response.name, response.file_path);
                        }
                    }
                    scope.fileUploader.uploadAll();
                } else {
                    submitViewPostValueGetter(btnName, isTimeout, passedVals, values);
                }
            }

            function submitViewPostValueGetter(btnName, isTimeout, passedVals, values) {
                values["buttonPressed"] = btnName;
                values["isTimeout"] = isTimeout ? 1 : 0;
                if (passedVals) {
                    angular.merge(values, passedVals);
                }
                $http.post(settings.directory + "test/session/" + settings.hash + "/submit", {
                    node_id: settings.nodeId,
                    values: values
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug && response.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE: {
                            settings.hash = response.hash;
                            timeLimit = response.timeLimit;
                            if (response.loaderHead.trim() != "" || response.loaderCss != "" || response.loaderJs != "" || response.loaderHtml != "")
                                settings.loaderHtml = joinHtml(response.loaderCss, response.loaderJs, response.loaderHtml);
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

            function getResults() {
                if (settings.clientDebug)
                    console.log("result");
                $http.post(settings.directory + "test/session/" + settings.hash + "/results", {
                    node_id: settings.nodeId
                }).success(function (response) {
                    if (settings.clientDebug)
                        console.log(response);
                    if (settings.debug && response.debug)
                        console.log(response.debug);
                    lastResponse = response;
                    lastResponseTime = new Date();
                    switch (lastResponse.code) {
                        case RESPONSE_RESULTS: {
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
                    var css = "";
                    var js = "";
                    var html = "";
                    switch (lastResponse.code) {
                        case RESPONSE_VIEW_TEMPLATE:
                        case RESPONSE_VIEW_FINAL_TEMPLATE:
                            css = lastResponse.templateCss.trim();
                            js = lastResponse.templateJs.trim();
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
                        case RESPONSE_SESSION_LIMIT_REACHED:
                            html = settings.sessionLimitReachedHtml;
                            break;
                        case RESPONSE_TEST_NOT_FOUND:
                            html = settings.testNotFoundHtml;
                            break;
                    }
                    displayState = DISPLAY_VIEW_SHOWN;

                    if (lastResponse.templateParams != null) {
                        scope.R = angular.extend(scope.R, angular.fromJson(lastResponse.templateParams));
                        testRunner.R = scope.R;
                    }

                    if (head != null && head.trim() !== "") {
                        angular.element("head").append($compile(head)(scope));
                    }

                    scope.html = joinHtml(css, js, html);
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

                if (lastResponse != null && lastResponse.templateParams != null) {
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
                element.find("input:text, input[type='range'], input[type='file'], input[type='hidden'], input:password, textarea, select, input:checkbox:checked, input:radio:checked").each(function () {
                    var name = $(this).attr("name");
                    var value = $(this).val();
                    if ($(this).attr("type") == "file") {
                        if ($(this)[0].files.length == 0)
                            return;
                        var file = $(this)[0].files[0];
                        scope.fileUploader.url = settings.directory + "test/session/" + settings.hash + "/upload";
                        scope.fileUploader.formData = [{
                            node_id: settings.nodeId
                        }, {
                            name: name
                        }];
                        scope.fileUploader.addToQueue(file);
                        return;
                    }
                    addPairToValues(vars, name, value);
                });
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

            var options = scope.options;
            if (settings.clientDebug)
                console.log(options);
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
