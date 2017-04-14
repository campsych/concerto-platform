concertoPanel.factory('DialogsService', function ($uibModal) {
    return {
        preDialog: function (title, tooltip, content, callback) {
            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'pre_dialog.html',
                controller: PreController,
                size: "lg",
                resolve: {
                    title: function () {
                        return title;
                    },
                    tooltip: function () {
                        return tooltip;
                    },
                    content: function () {
                        return content;
                    }
                }
            });

            if (callback) {
                instance.result.then(function (response) {
                    callback(response);
                });
            }
            return instance;
        },
        ckeditorDialog: function (title, tooltip, value, resultCallback, cancelCallback) {
            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "ckeditor_dialog.html",
                controller: CKEditorController,
                resolve: {
                    title: function () {
                        return title;
                    },
                    tooltip: function () {
                        return tooltip;
                    },
                    value: function () {
                        return value;
                    }
                },
                size: "lg"
            });

            if (resultCallback || cancelCallback) {
                instance.result.then(function (response) {
                    if (resultCallback)
                        resultCallback(response);
                }, function () {
                    if (cancelCallback)
                        cancelCallback();
                });
            }
            return instance;
        },
        alertDialog: function (title, content, type, size, callback) {
            if (!type)
                type = "info";
            if (!size)
                size = "sm";

            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                controller: AlertController,
                size: size,
                resolve: {
                    title: function () {
                        return title;
                    },
                    content: function () {
                        return content;
                    },
                    type: function () {
                        return type;
                    }
                }
            });

            if (callback) {
                instance.result.then(function (response) {
                    callback(response);
                });
            }
            return instance;
        },
        confirmDialog: function (title, content, resultCallback, cancelCallback) {
            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
                controller: ConfirmController,
                size: "sm",
                resolve: {
                    title: function () {
                        return title;
                    },
                    content: function () {
                        return content;
                    }
                }
            });

            if (resultCallback || cancelCallback) {
                instance.result.then(function (response) {
                    if (resultCallback)
                        resultCallback(response);
                }, function () {
                    if (cancelCallback)
                        cancelCallback();
                });
            }
            return instance;
        },
        textareaDialog: function (title, value, tooltip, readonly, resultCallback, cancelCallback) {
            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "textarea_dialog.html",
                controller: TextareaController,
                resolve: {
                    readonly: function () {
                        return readonly;
                    },
                    value: function () {
                        return value;
                    },
                    title: function () {
                        return title;
                    },
                    tooltip: function () {
                        return tooltip;
                    }
                },
                size: "lg"
            });

            if (resultCallback || cancelCallback) {
                instance.result.then(function (response) {
                    if (resultCallback)
                        resultCallback(response);
                }, function () {
                    if (cancelCallback)
                        cancelCallback();
                });
            }
            return instance;
        }
    };
});
