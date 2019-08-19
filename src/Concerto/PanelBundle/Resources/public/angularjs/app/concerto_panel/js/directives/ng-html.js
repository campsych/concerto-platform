(function () {
    'use strict';

    var module = angular.module('ng-html', []);

    module.directive('ngHtml', ['$compile', function ($compile) {
        return {
            restrict: 'A',
            link: function (scope, element, attrs) {
                scope.$watch(function () {
                    return scope.$eval(attrs.ngHtml);
                }, function (value) {
                    element.html(value && value.toString());
                    $compile(element.contents())(scope);
                });
            }
        };
    }]);
}());