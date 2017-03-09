function AdministrationController($scope, $http, $uibModal, AdministrationSettingsService, SessionCountCollectionService) {
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

    $scope.chart = {
        data: [[]],
        options: {
            scales: {
                xAxes: [{
                        type: 'linear',
                        position: 'bottom',
                        ticks: {
                            callback: function (value) {
                                var d = new Date(value * 1000);
                                var datestring = ("0" + d.getDate()).slice(-2) + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" +
                                        d.getFullYear() + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
                                return datestring;
                            }
                        }
                    }]
            },
            tooltips: {
                callbacks: {
                    title: function (tooltipItem, data) {
                        var d = new Date(tooltipItem[0].xLabel * 1000);
                        var datestring = ("0" + d.getDate()).slice(-2) + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" +
                                d.getFullYear() + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
                        return datestring;
                    }
                }
            }
        }
    };
    $scope.refreshUsageChart = function () {
        SessionCountCollectionService.fetchObjectCollection({}, function () {
            $scope.chart.data[0] = SessionCountCollectionService.collection;
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
    $scope.refreshUsageChart();
}

concertoPanel.controller('AdministrationController', ["$scope", "$http", "$uibModal", "AdministrationSettingsService", "SessionCountCollectionService", AdministrationController]);
