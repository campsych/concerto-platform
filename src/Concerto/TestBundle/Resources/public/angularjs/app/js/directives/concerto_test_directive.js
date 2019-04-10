'use strict';
testRunner.settings = {
  unresumableHtml: null,
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

      if(testRunner.settings.unresumableHtml === null) testRunner.settings.unresumableHtml = $templateCache.get("unresumable_template.html");
      if(testRunner.settings.finishedHtml === null) testRunner.settings.finishedHtml = $templateCache.get("finished_template.html");
      if(testRunner.settings.testErrorHtml === null) testRunner.settings.testErrorHtml = $templateCache.get("test_error_template.html");
      if(testRunner.settings.serverErrorHtml === null) testRunner.settings.serverErrorHtml = $templateCache.get("server_error_template.html");
      if(testRunner.settings.clientErrorHtml === null) testRunner.settings.clientErrorHtml = $templateCache.get("client_error_template.html");
      if(testRunner.settings.sessionLimitReachedHtml === null) testRunner.settings.sessionLimitReachedHtml = $templateCache.get("session_limit_reached_template.html");
      if(testRunner.settings.testNotFoundHtml === null) testRunner.settings.testNotFoundHtml = $templateCache.get("test_not_found_template.html");
      if(testRunner.settings.sessionLostHtml === null) testRunner.settings.sessionLostHtml = $templateCache.get("session_lost_template.html");
      if(testRunner.settings.connectionRetryHtml === null) testRunner.settings.connectionRetryHtml = $templateCache.get("connection_retry_template.html");
      if(testRunner.settings.loaderHtml === null) testRunner.settings.loaderHtml = $templateCache.get("loading_template.html");

      $window.addEventListener('unload', function (e) {
        clearTimer();

        $.ajax({
          type: "POST",
          url: internalSettings.directory + "test/session/" + lastResponse.hash + "/kill",
          async: false,
          data: {}
        });
      });

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
      var RESPONSE_UNRESUMABLE = 11;
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
        clientDebug: false,
        params: null,
        directory: "/",
        testSlug: null,
        testName: null,
        hash: null,
        keepAliveInterval: 0
      }, scope.options);
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
      scope.retryTimeLeft = "";

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
        $http.post(internalSettings.directory + "test/session/" + lastResponse.hash + "/log", {
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
        if (internalSettings.clientDebug)
          console.log("start keep alive (" + internalSettings.keepAliveInterval + ")");
        if (internalSettings.keepAliveInterval > 0) {
          keepAliveTimerPromise = $interval(function () {
            $http.post(internalSettings.directory + "test/session/" + lastResponse.hash + "/keepalive", {
            }).success(function (response) {
              if (internalSettings.clientDebug)
                console.log("keep-alive ping");
              if (displayState !== DISPLAY_VIEW_SHOWN || lastResponse == null || lastResponse.code !== RESPONSE_VIEW_TEMPLATE || response.code !== RESPONSE_KEEPALIVE_CHECKIN)
                $interval.cancel(keepAliveTimerPromise);
            });
          }, internalSettings.keepAliveInterval * 1000);
        }
      }

      function timeTick() {
        if (timer > 0) {
          timer--;
          scope.timeLeft = dateFilter(new Date(0, 0, 0, 0, 0, timer), testRunner.settings.timeFormat);
          if (timer === 0) {
            scope.submitView("timeout", true);
          }
        }
      }

      function startNewTest() {
        if (internalSettings.clientDebug)
          console.log("start");

        showLoader();
        var path = "";
        if (internalSettings.debug) {
          path = internalSettings.directory + "admin/test/" + internalSettings.testSlug + "/session/start/debug/" + encodeURIComponent(internalSettings.params);
        } else {
          if (internalSettings.testName !== null) {
            path = internalSettings.directory + "test_n/" + internalSettings.testName + "/session/start/" + encodeURIComponent(internalSettings.params);
          } else {
            path = internalSettings.directory + "test/" + internalSettings.testSlug + "/session/start/" + encodeURIComponent(internalSettings.params);
          }
        }

        $http.post(path, {
        }).success(function (response) {
          if (internalSettings.clientDebug)
            console.log(response);
          if (internalSettings.debug && response.debug)
            console.log(response.debug);
          lastResponse = response;
          lastResponseTime = new Date();
          isViewReady = true;

          switch (lastResponse.code) {
            case RESPONSE_VIEW_TEMPLATE:
            case RESPONSE_VIEW_FINAL_TEMPLATE: {
              internalSettings.hash = response.hash;
              timeLimit = response.timeLimit;
              updateLoader(response.data);
              break;
            }
          }

          showView();
        }).error(function(error, status) {
          if (status >= 500 && status < 600) {
            if (internalSettings.clientDebug)
              console.log("server error");
            isViewReady = true;
            showView(testRunner.settings.serverErrorHtml);
          } else if (status >= 400 && status < 500) {
            if (internalSettings.clientDebug)
              console.log("client error");
            isViewReady = true;
            showView(testRunner.settings.clientErrorHtml);
          }
        });
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
        if (internalSettings.clientDebug)
          console.log("worker", name);

        var values = getControlsValues();
        if (passedVals) {
          angular.merge(values, passedVals);
        }
        values["bgWorker"] = name;

        $http.post(internalSettings.directory + "test/session/" + internalSettings.hash + "/worker", {
          values: values
        }).success(function (response) {
          if (internalSettings.clientDebug)
            console.log(response);
          if (internalSettings.debug && response.debug)
            console.log(response.debug);

          if (successCallback != null) {
            successCallback.call(this, response.data);
          }
        }).error(function (error, status) {
          if (internalSettings.clientDebug)
            console.log("worker failed");

          if (errorCallback != null) {
            errorCallback.call(this, error, status);
          }
        });
      }

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

        if (internalSettings.clientDebug)
          console.log("submit");

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
        values["buttonPressed"] = btnName ? btnName : "";
        values["isTimeout"] = isTimeout ? 1 : 0;
        if (passedVals) {
          angular.merge(values, passedVals);
        }
        $http.post(internalSettings.directory + "test/session/" + internalSettings.hash + "/submit", {
          values: values
        }).success(function (response) {
          if (internalSettings.clientDebug)
            console.log(response);
          if (internalSettings.debug && response.debug)
            console.log(response.debug);
          lastResponse = response;
          lastResponseTime = new Date();
          switch (lastResponse.code) {
            case RESPONSE_VIEW_TEMPLATE:
            case RESPONSE_VIEW_FINAL_TEMPLATE: {
              internalSettings.hash = response.hash;
              timeLimit = response.timeLimit;
              updateLoader(response.data);
              break;
            }
          }

          isViewReady = true;
          showView();
        }).error(function (error, status) {
          if (status === -1) {
            if (internalSettings.clientDebug)
              console.log("connection failed");
            showConnectionProblems(btnName, isTimeout, passedVals, values);
          } else if (status >= 500 && status < 600) {
            if (internalSettings.clientDebug)
              console.log("server error");
            isViewReady = true;
            showView(testRunner.settings.serverErrorHtml);
          } else if (status >= 400 && status < 500) {
            if (internalSettings.clientDebug)
              console.log("client error");
            isViewReady = true;
            showView(testRunner.settings.clientErrorHtml);
          }
        });
      }

      function showConnectionProblems(btnName, isTimeout, passedVals, values) {
        initializeRetryTimer(btnName, isTimeout, passedVals, values);
        scope.html = testRunner.settings.connectionRetryHtml;
      }

      function initializeRetryTimer(btnName, isTimeout, passedVals, values) {
        if (retryTimeTotal > 0) {
          if (internalSettings.clientDebug)
            console.log("connection retry in " + retryTimeTotal + " secs");
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
        if (internalSettings.clientDebug)
          console.log("showView (" + displayState + ")");
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
              case RESPONSE_UNRESUMABLE:
                html = testRunner.settings.unresumableHtml;
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

            if (typeof(lastResponse.data) !== 'undefined' && lastResponse.data.templateParams != null) {
              scope.R = angular.extend(scope.R, angular.fromJson(lastResponse.data.templateParams));
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
        if (internalSettings.clientDebug)
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
        if (internalSettings.clientDebug)
          console.log("showLoader");
        displayState = DISPLAY_LOADER_SHOWN;

        if (internalSettings.loaderHead != null)
          angular.element("head").append($compile(internalSettings.loaderHead)(scope));

        scope.html = testRunner.settings.loaderHtml;
      }

      function hideLoader(content) {
        if (internalSettings.clientDebug)
          console.log("hideLoader");
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
        scope.fileUploader.url = internalSettings.directory + "test/session/" + internalSettings.hash + "/upload";
        scope.fileUploader.formData = [{
          name: name
        }];
        if (typeof(file) !== 'File') {
          file = new File([file], name);
        }
        scope.fileUploader.addToQueue(file);
      };

      testRunner.R = scope.R;
      testRunner.submitView = scope.submitView;
      testRunner.runWorker = scope.runWorker;
      testRunner.logClientSideError = scope.logClientSideError;
      testRunner.addExtraControl = scope.addExtraControl;
      testRunner.addEventListener = scope.addEventListener;
      testRunner.removeEventListener = scope.removeEventListener;
      testRunner.queueUpload = scope.queueUpload;

      var options = scope.options;
      if (internalSettings.clientDebug)
        console.log(options);
      if (options.testSlug != null || options.testName != null) {
        startNewTest();
        return;
      }
      if (internalSettings.clientDebug)
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
