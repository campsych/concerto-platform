function AdministrationController($scope, $http, $uibModal, AdministrationSettingsService) {
    $scope.tabStateName = "administration";
    $scope.tabIndex = 6;
    $scope.updateSettingsMapPath = Paths.ADMINISTRATION_SETTINGS_MAP_UPDATE;
    $scope.settingsMap = {};

    $scope.persistSettings = function () {
        $http.post($scope.updateSettingsMapPath, {
            map: angular.toJson($scope.settingsMap)
        }).then(function (response) {
            switch (response.data.result) {
                case 0:
                    $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                        controller: AlertController,
                        size: "sm",
                        resolve: {
                            title: function () {
                                return Trans.DIALOG_TITLE_SAVE;
                            },
                            content: function () {
                                return Trans.DIALOG_MESSAGE_SAVED;
                            },
                            type: function () {
                                return "success";
                            }
                        }
                    });
                    break;
            }
        });
    };

    $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
        if (toState.name === $scope.tabStateName) {
            $scope.tab.activeIndex = $scope.tabIndex;
        }
    });

    AdministrationSettingsService.fetchSettingsMap(null, function () {
        $scope.settingsMap = AdministrationSettingsService.settingsMap;
    });
}

concertoPanel.controller('AdministrationController', ["$scope", "$http", "$uibModal", "AdministrationSettingsService", AdministrationController]);
