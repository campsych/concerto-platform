function AdministrationController($scope, $http, $uibModal, AdministrationSettingsService, SessionCountCollectionService, uiGridConstants, MessagesCollectionService) {
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
                var date = new Date();
                date.setHours(0);
                date.setMinutes(0);
                date.setSeconds(0);
                date.setMilliseconds(0);
                filter.min = Math.round(date.getTime() / 1000);
                break;
            }
            case 2:
            {
                $scope.chart.filter.minDate.setHours(0);
                $scope.chart.filter.minDate.setMinutes(0);
                $scope.chart.filter.minDate.setSeconds(0);
                $scope.chart.filter.minDate.setMilliseconds(0);
                filter.min = Math.round($scope.chart.filter.minDate.getTime() / 1000);
                filter.max = filter.min + 86399;
                break;
            }
            case 3:
            {
                $scope.chart.filter.minDate.setHours(0);
                $scope.chart.filter.minDate.setMinutes(0);
                $scope.chart.filter.minDate.setSeconds(0);
                $scope.chart.filter.minDate.setMilliseconds(0);
                $scope.chart.filter.maxDate.setHours(0);
                $scope.chart.filter.maxDate.setMinutes(0);
                $scope.chart.filter.maxDate.setSeconds(0);
                $scope.chart.filter.maxDate.setMilliseconds(0);
                filter.min = Math.round($scope.chart.filter.minDate.getTime() / 1000);
                filter.max = Math.round($scope.chart.filter.maxDate.getTime() / 1000) + 86399;
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

    $scope.messageCollection = [];
    $scope.messageOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "messageCollection",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.messageOptions.enableFiltering = !$scope.messageOptions.enableFiltering;
                    $scope.messageGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.messageGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.MESSAGES_LIST_FIELD_TIME,
                field: "time"
            }, {
                displayName: Trans.MESSAGES_LIST_FIELD_CATEGORY,
                field: "category"
            }, {
                displayName: Trans.MESSAGES_LIST_FIELD_MESSAGE,
                field: "message",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                        '<i class="glyphicon glyphicon-align-justify clickable" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true" ng-click="grid.appScope.showSingleTextareaModal(COL_FIELD, true, \'' + Trans.MESSAGES_LIST_FIELD_MESSAGE + '\',\'' + Trans.MESSAGES_LIST_FIELD_MESSAGE + '\')"></i>' +
                        "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate: '<div class="ui-grid-cell-contents" align="center"><button type="button" class="btn btn-danger btn-xs" ng-click="deleteMessage(row.entity.id);">' + Trans.MESSAGES_LIST_BUTTONS_DELETE + '</button></div>',
                width: 60
            }
        ]
    };

    $scope.refreshMessages = function () {
        MessagesCollectionService.fetchObjectCollection(function () {
            $scope.messageCollection = MessagesCollectionService.collection;
        });
    };

    $scope.deleteMessage = function (id) {
        //@TODO
    };

    $scope.deleteSelectedMessages = function () {
        //@TODO
    };

    $scope.deleteAllMessages = function () {
        //@TODO
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
    $scope.refreshMessages();
}

concertoPanel.controller('AdministrationController', ["$scope", "$http", "$uibModal", "AdministrationSettingsService", "SessionCountCollectionService", "uiGridConstants", "MessagesCollectionService", AdministrationController]);
