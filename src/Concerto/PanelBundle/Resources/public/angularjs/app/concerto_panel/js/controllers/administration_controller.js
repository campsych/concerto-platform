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

    $scope.formatTimestamp = function (timestamp) {
        var d = new Date(timestamp * 1000);
        var datestring = ("0" + d.getDate()).slice(-2) + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" +
                d.getFullYear() + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ":" + ("0" + d.getSeconds()).slice(-2);
        return datestring;
    };

    $scope.chart = {
        filter: {
            id: 1,
            minDate: new Date(),
            maxDate: new Date()
        },
        data: [[]],
        datasets: [
            {
                lineTension: 0
            }
        ],
        options: {
            scales: {
                xAxes: [{
                        type: 'linear',
                        position: 'bottom',
                        ticks: {
                            callback: function (value) {
                                return $scope.formatTimestamp(value);
                            }
                        }
                    }]
            },
            tooltips: {
                callbacks: {
                    title: function (tooltipItem, data) {
                        return $scope.formatTimestamp(tooltipItem[0].xLabel);
                    }
                }
            }
        }
    };
    $scope.usageChartFilters = [
        {
            id: 1,
            label: Trans.ADMINISTRATION_USAGE_DATA_FILTER_TODAY
        }, {
            id: 2,
            label: Trans.ADMINISTRATION_USAGE_DATA_FILTER_SPECIFIC_DATE
        }, {
            id: 3,
            label: Trans.ADMINISTRATION_USAGE_DATA_FILTER_DATE_RANGE
        }
    ];
    $scope.refreshUsageChart = function () {
        var filter = {};
        switch ($scope.chart.filter.id) {
            case 1:
            {
                filter.min = Math.round(Date.now() / 1000) - 86399;
                break;
            }
            case 2:
            {
                filter.min = Math.round($scope.chart.filter.minDate.getTime() / 1000) - 86399;
                filter.max = filter.min + 86399;
                break;
            }
            case 3:
            {
                filter.min = Math.round($scope.chart.filter.minDate.getTime() / 1000) - 86399;
                filter.max = Math.round($scope.chart.filter.maxDate.getTime() / 1000);
                break;
            }
        }

        SessionCountCollectionService.fetchObjectCollection(filter, function () {
            $scope.chart.data[0] = SessionCountCollectionService.collection;
        });
    };

    $scope.clearUsageDate = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.ADMINISTRATION_DIALOG_TITLE_CLEAR;
                },
                content: function () {
                    return Trans.ADMINISTRATION_DIALOG_CONFIRM_CLEAR;
                }
            }
        });

        modalInstance.result.then(function (response) {
            $http.post(Paths.ADMINISTRATION_SESSION_COUNT_CLEAR, {}).then(function () {
                $scope.refreshUsageChart();
            });
        }, function () {
        });
    }

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
