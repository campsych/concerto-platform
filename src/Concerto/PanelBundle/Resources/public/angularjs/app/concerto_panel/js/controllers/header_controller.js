function HeaderController($scope, DialogsService, $http, $uibModal) {
    $scope.mfaEnabled = false;

    $scope.logout = function () {
        location.href = Paths.LOGOUT;
    };

    $scope.changeLocale = function (key) {
        location.href = Paths.LOCALE.pf(key);
    };

    $scope.enableMFA = function () {
        $http.post(Paths.ENABLE_MFA, {}).then(function (response) {
            $scope.mfaEnabled = true;
            $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'mfa_enabled_dialog.html',
                controller: MfaEnabledController,
                size: "lg",
                resolve: {
                    qrCode: function () {
                        return response.data.qrCode;
                    },
                    secret: function () {
                        return response.data.secret;
                    }
                }
            });
        });
    };

    $scope.disableMFA = function () {
        DialogsService.confirmDialog(
            Trans.DIALOG_DISABLING_MFA_TITLE,
            Trans.DIALOG_DISABLING_MFA_CONTENT,
            function () {
                $http.post(Paths.DISABLE_MFA, {}).then(function (response) {
                    $scope.mfaEnabled = false;
                });
            }
        );
    };
}

concertoPanel.controller('HeaderController', ["$scope", "DialogsService", "$http", "$uibModal", HeaderController]);