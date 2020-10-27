function AdministrationController($scope, $http, $uibModal, $filter, AdministrationSettingsService, SessionCountCollectionService, uiGridConstants, MessagesCollectionService, ScheduledTasksCollectionService, ApiClientsCollectionService, DialogsService, TestCollectionService) {
    $scope.tabStateName = "administration";
    $scope.updateSettingsMapPath = Paths.ADMINISTRATION_SETTINGS_MAP_UPDATE;
    $scope.deleteMessagePath = Paths.ADMINISTRATION_MESSAGES_DELETE;
    $scope.clearMessagePath = Paths.ADMINISTRATION_MESSAGES_CLEAR;
    $scope.deleteApiClientsPath = Paths.ADMINISTRATION_API_CLIENTS_DELETE;
    $scope.clearApiClientsPath = Paths.ADMINISTRATION_API_CLIENTS_CLEAR;
    $scope.addApiClientPath = Paths.ADMINISTRATION_API_CLIENTS_ADD;
    $scope.packageReportPath = Paths.ADMINISTRATION_PACKAGES_STATUS;
    $scope.exposedSettingsMap = {};
    $scope.internalSettingsMap = {};
    $scope.dialogsService = DialogsService;
    $scope.testCollectionService = TestCollectionService;

    $scope.persistSettings = function () {
        $http.post($scope.updateSettingsMapPath, {
            map: angular.toJson($scope.exposedSettingsMap)
        }).then(function (response) {
            switch (response.data.result) {
                case 0:
                    $scope.dialogsService.alertDialog(
                        Trans.DIALOG_TITLE_SAVE,
                        Trans.DIALOG_MESSAGE_SAVED,
                        "success"
                    );
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
            case 1: {
                var date = new Date();
                date.setHours(0);
                date.setMinutes(0);
                date.setSeconds(0);
                date.setMilliseconds(0);
                filter.min = Math.round(date.getTime() / 1000);
                break;
            }
            case 2: {
                $scope.chart.filter.minDate.setHours(0);
                $scope.chart.filter.minDate.setMinutes(0);
                $scope.chart.filter.minDate.setSeconds(0);
                $scope.chart.filter.minDate.setMilliseconds(0);
                filter.min = Math.round($scope.chart.filter.minDate.getTime() / 1000);
                filter.max = filter.min + 86399;
                break;
            }
            case 3: {
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
        $scope.dialogsService.confirmDialog(
            Trans.ADMINISTRATION_DIALOG_TITLE_CLEAR,
            Trans.ADMINISTRATION_DIALOG_CONFIRM_CLEAR,
            function (response) {
                $http.post(Paths.ADMINISTRATION_SESSION_COUNT_CLEAR, {}).then(function () {
                    $scope.refreshUsageChart();
                });
            }
        );
    };

    $scope.messageCollection = [];
    $scope.messageOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "messageCollection",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
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
                field: "time",
                sort: {direction: 'desc', priority: 0}
            }, {
                displayName: Trans.MESSAGES_LIST_FIELD_CATEGORY,
                field: "category",
                cellTemplate: "<div class='ui-grid-cell-contents'>{{grid.appScope.getMessageCategoryLabel(row.entity.category)}}</div>"
            }, {
                displayName: Trans.MESSAGES_LIST_FIELD_SUBJECT,
                field: "subject"
            }, {
                displayName: Trans.MESSAGES_LIST_FIELD_MESSAGE,
                field: "message",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.dialogsService.alertDialog(row.entity.subject, COL_FIELD, \'info\',\'lg\')"></i>' +
                    "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate: '<div class="ui-grid-cell-contents" align="center"><button type="button" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteMessage(row.entity.id);">' + Trans.MESSAGES_LIST_BUTTONS_DELETE + '</button></div>',
                width: 60
            }
        ]
    };

    $scope.getMessageCategoryLabel = function (id) {
        switch (id) {
            case 0:
                return Trans.MESSAGES_LIST_FIELD_CATEGORY_SYSTEM;
            case 1:
                return Trans.MESSAGES_LIST_FIELD_CATEGORY_TEST;
            case 2:
                return Trans.MESSAGES_LIST_FIELD_CATEGORY_GLOBAL;
            case 3:
                return Trans.MESSAGES_LIST_FIELD_CATEGORY_LOCAL;
            case 4:
                return Trans.MESSAGES_LIST_FIELD_CATEGORY_CHANGELOG;
        }
    };

    $scope.refreshMessages = function () {
        MessagesCollectionService.fetchObjectCollection(function () {
            $scope.messageCollection = MessagesCollectionService.collection;
        });
    };

    $scope.deleteMessage = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        $scope.dialogsService.confirmDialog(
            Trans.MESSAGES_DIALOGS_TITLE_DELETE,
            Trans.MESSAGES_DIALOGS_MESSAGE_DELETE,
            function (response) {
                $http.post($scope.deleteMessagePath.pf(ids), {}).then(function (data) {
                    $scope.refreshMessages();
                });
            }
        );
    };

    $scope.deleteSelectedMessages = function () {
        var ids = [];
        for (var i = 0; i < $scope.messageGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.messageGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.deleteMessage(ids);
    };

    $scope.deleteAllMessages = function () {
        $scope.dialogsService.confirmDialog(
            Trans.MESSAGES_DIALOGS_TITLE_CLEAR,
            Trans.MESSAGES_DIALOGS_MESSAGE_CLEAR,
            function (response) {
                $http.post($scope.clearMessagePath, {}).then(function (data) {
                    $scope.refreshMessages();
                });
            }
        );
    };

    $scope.packagesTasksCollection = [];
    $scope.packagesTasksOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "packagesTasksCollection",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.packagesTasksOptions.enableFiltering = !$scope.packagesTasksOptions.enableFiltering;
                    $scope.packagesTasksGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.packagesTasksGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.TASKS_LIST_FIELD_UPDATED,
                field: "updated",
                sort: {direction: 'desc', priority: 0}
            }, {
                displayName: Trans.TASKS_LIST_FIELD_STATUS,
                field: "status",
                cellTemplate: "<div class='ui-grid-cell-contents'>{{row.entity.status|taskStatusLabel}}</div>"
            }, {
                displayName: Trans.TASKS_LIST_FIELD_DESCRIPTION,
                field: "description"
            }, {
                displayName: Trans.TASKS_LIST_FIELD_OUTPUT,
                field: "output",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.dialogsService.textareaDialog(row.entity.updated, COL_FIELD, grid.appScope.getTaskStatusLabel(row.entity.status), true)"></i>' +
                    "</div>"
            }
        ]
    };

    $scope.getTaskStatusLabel = function (status) {
        return $filter("taskStatusLabel")(status);
    };

    $scope.apiClientsCollection = [];
    $scope.apiClientsOptions = {
        enableFiltering: false,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "apiClientsCollection",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.apiClientsOptions.enableFiltering = !$scope.apiClientsOptions.enableFiltering;
                    $scope.apiClientsGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            $scope.apiClientsGridApi = gridApi;
        },
        columnDefs: [
            {
                displayName: Trans.API_CLIENTS_LIST_FIELD_ID,
                field: "fullId"
            }, {
                displayName: Trans.API_CLIENTS_LIST_FIELD_SECRET,
                field: "secret"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate: '<div class="ui-grid-cell-contents" align="center"><button type="button" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteApiClient(row.entity.id);">' + Trans.API_CLIENTS_LIST_BUTTONS_DELETE + '</button></div>',
                width: 60
            }
        ]
    };

    $scope.deleteApiClient = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        $scope.dialogsService.confirmDialog(
            Trans.API_CLIENTS_DIALOGS_TITLE_DELETE,
            Trans.API_CLIENTS_DIALOGS_MESSAGE_DELETE,
            function (response) {
                $http.post($scope.deleteApiClientsPath.pf(ids), {}).then(function (data) {
                    $scope.refreshApiClients();
                });
            }
        );
    };

    $scope.deleteSelectedApiClients = function () {
        var ids = [];
        for (var i = 0; i < $scope.apiClientsGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.apiClientsGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.deleteApiClient(ids);
    };

    $scope.deleteAllApiClients = function () {
        $scope.dialogsService.confirmDialog(
            Trans.API_CLIENTS_DIALOGS_TITLE_CLEAR,
            Trans.API_CLIENTS_DIALOGS_MESSAGE_CLEAR,
            function (response) {
                $http.post($scope.clearApiClientsPath, {}).then(function (data) {
                    $scope.refreshApiClients();
                });
            }
        );
    };

    $scope.addApiClient = function () {
        $http.post($scope.addApiClientPath, {}).then(function (response) {
            $scope.refreshApiClients();
        });
    };

    $scope.packagesReport = function () {
        $http.post($scope.packageReportPath, {}).then(function (response) {
            switch (response.data.result) {
                case 0:
                    $scope.dialogsService.preDialog(
                        Trans.PACKAGES_DIALOG_TITLE_REPORT,
                        Trans.PACKAGES_DIALOG_TITLE_REPORT_TOOLTIP,
                        response.data.output
                    );
                    break;
                default:
                    $scope.dialogsService.alertDialog(
                        Trans.PACKAGES_DIALOG_TITLE_REPORT,
                        Trans.PACKAGES_DIALOG_CONTENT_REPORT_FAILED,
                        "danger");
                    break;
            }
        });
    };

    $scope.installPackage = function () {
        let modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'r_package_installation_dialog.html',
            controller: RPackageInstallController,
            size: "lg"
        });

        modalInstance.result.then(function (installOptions) {
            $http.post(Paths.ADMINISTRATION_TASKS_PACKAGE_INSTALL, {
                install_options: angular.toJson(installOptions)
            }).then(function (response) {
                $scope.refreshAllTaskRelated();

                if (response.data.result !== 0) {
                    $scope.dialogsService.alertDialog(
                        Trans.PACKAGES_DIALOG_TITLE_INSTALLATION_FAILED,
                        response.data.errors.join("<br/>"),
                        "danger",
                        "lg"
                    );
                }
            });
        }, function () {
        });
    };

    $scope.refreshApiClients = function () {
        ApiClientsCollectionService.fetchObjectCollection(function () {
            $scope.apiClientsCollection = ApiClientsCollectionService.collection;
        });
    };

    $scope.refreshTasks = function () {
        ScheduledTasksCollectionService.fetchObjectCollection(function () {
            $scope.packagesTasksCollection = ScheduledTasksCollectionService.packagesCollection;
        });
    };

    $scope.refreshSettings = function () {
        AdministrationSettingsService.fetchSettingsMap(null, function () {
            $scope.exposedSettingsMap = AdministrationSettingsService.exposedSettingsMap;
            $scope.internalSettingsMap = AdministrationSettingsService.internalSettingsMap;
        });
    };

    $scope.refreshAllTaskRelated = function () {
        $scope.refreshSettings();
        $scope.refreshMessages();
        $scope.refreshTasks();
    };

    $scope.refreshAllTaskRelated();
    $scope.refreshUsageChart();
    $scope.refreshApiClients();
}

concertoPanel.controller('AdministrationController', ["$scope", "$http", "$uibModal", "$filter", "AdministrationSettingsService", "SessionCountCollectionService", "uiGridConstants", "MessagesCollectionService", "ScheduledTasksCollectionService", "ApiClientsCollectionService", "DialogsService", "TestCollectionService", AdministrationController]);
