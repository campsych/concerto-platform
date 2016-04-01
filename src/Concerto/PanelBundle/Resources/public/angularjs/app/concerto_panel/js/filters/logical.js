angular.module('concertoPanel').filter('logical', [
    function () {
        return function (value) {
            return value == 1 ? Trans.TEST_VARS_PARAMS_LIST_FIELD_URL_YES : Trans.TEST_VARS_PARAMS_LIST_FIELD_URL_NO;
        };
    }
]);