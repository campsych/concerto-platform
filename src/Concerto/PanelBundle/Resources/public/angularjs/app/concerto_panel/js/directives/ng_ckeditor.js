angular.module('concertoPanel').directive('ckeditor', function () {
    return {
        require: '?ngModel',
        scope: {
            options: "=ckeditor"
        },
        link: function (scope, elm, attr, ngModel) {

            var ck = CKEDITOR.replace(elm[0], scope.options);
            if (!ngModel)
                return;
            ck.on('instanceReady', function () {
                ck.setData(ngModel.$viewValue);
            });
            function updateModel() {
                scope.$apply(function () {
                    ngModel.$setViewValue(ck.getData());
                });
            }
            ck.on('change', updateModel);
            ck.on('key', updateModel);
            ck.on('dataReady', updateModel);

            ngModel.$render = function (value) {
                ck.setData(ngModel.$viewValue);
            };
        }
    };
});