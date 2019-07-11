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

            function updateModel() {
                ngModel.$setViewValue(ck.getData());
            }

            ck.on('instanceReady', function () {
                for (var e in CKEDITOR.tools.extend(
                    {src: 1},
                    CKEDITOR.dtd.$block,
                    CKEDITOR.dtd.$inline,
                    CKEDITOR.dtd.$intermediate,
                    CKEDITOR.dtd.$nonBodyContent
                )) {
                    ck.dataProcessor.writer.setRules(e, {
                        indent: true,
                        breakBeforeOpen: true,
                        breakAfterOpen: true,
                        breakBeforeClose: true,
                        breakAfterClose: true
                    });
                }

                ck.on('change', updateModel);
                ck.on('key', updateModel);

                if (ngModel.$viewValue !== '') ck.setData(ngModel.$viewValue);

                ngModel.$render = function (value) {
                    ck.setData(ngModel.$viewValue);
                };
            });
        }
    };
});