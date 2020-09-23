angular.module('concertoPanel').filter('taskStatusLabel', [
    function () {
        return function (status) {
            switch (status) {
                case 1:
                    return Trans.TASKS_LIST_FIELD_STATUS_ONGOING;
                case 2:
                    return Trans.TASKS_LIST_FIELD_STATUS_COMPLETED;
                case 3:
                    return Trans.TASKS_LIST_FIELD_STATUS_FAILED;
                case 4:
                    return Trans.TASKS_LIST_FIELD_STATUS_CANCELED;
                default:
                    return Trans.TASKS_LIST_FIELD_STATUS_PENDING;
            }
        };
    }
]);