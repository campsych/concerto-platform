'use strict';

testRunner.service('sessionResume', ['$cookies', '$filter',
    function($cookies, $filter) {

        this.getSessionCookie = function() {
            var session = $cookies.get('concerto_test_sessions');
            if (session == null)
                return [];
            else
                return angular.fromJson(session);
        };

        this.resetSessionCookie = function() {
            $cookies.remove('concerto_test_sessions');
        };

        this.saveSessionCookie = function(hash, testId) {
            var session = this.getSessionCookie();
            var date = new Date();
            var exists = false;
            for (var i = 0; i < session.length; i++) {
                var elem = session[i];
                if (elem.testId === testId) {
                    exists = true;
                    session[i].date = date.toUTCString();
                    session[i].hash = hash;
                }
            }
            if (!exists) {
                session.push({
                    hash: hash,
                    date: date.toUTCString(),
                    testId: testId
                });
            }
            $cookies.put('concerto_test_sessions', $filter('json')(session));
        };

        this.removeSessionCookie = function(hash) {
            var session = this.getSessionCookie();
            var result = [];
            for (var i = 0; i < session.length; i++) {
                var elem = session[i];
                if (elem.hash !== hash) {
                    result.push(elem);
                }
            }
            $cookies.put('concerto_test_sessions', $filter('json')(result));
        };

        this.getSessionObject = function(testId) {
            var session = this.getSessionCookie();
            for (var i = 0; i < session.length; i++) {
                var s = session[i];
                if (s.testId === testId)
                    return s;
            }
            return null;
        };
    }
]);