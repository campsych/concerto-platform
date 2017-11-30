function AdministrationController($scope, $http, $uibModal, AdministrationSettingsService, SessionCountCollectionService, uiGridConstants, MessagesCollectionService, ScheduledTasksCollectionService, ApiClientsCollectionService, DialogsService) {
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
          $http.post($scope.deleteMessagePath.pf(ids), {}).success(function (data) {
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
          $http.post($scope.clearMessagePath, {}).success(function (data) {
            $scope.refreshMessages();
          });
        }
    );
  };

  $scope.tasksCollection = [];
  $scope.tasksOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    data: "tasksCollection",
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.tasksOptions.enableFiltering = !$scope.tasksOptions.enableFiltering;
          $scope.tasksGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.tasksGridApi = gridApi;
    },
    columnDefs: [
      {
        displayName: Trans.TASKS_LIST_FIELD_UPDATED,
        field: "updated",
        sort: {direction: 'desc', priority: 0}
      }, {
        displayName: Trans.TASKS_LIST_FIELD_TYPE,
        field: "type",
        cellTemplate: "<div class='ui-grid-cell-contents'>{{grid.appScope.getTasksTypeLabel(row.entity.type)}}</div>"
      }, {
        displayName: Trans.TASKS_LIST_FIELD_STATUS,
        field: "status",
        cellTemplate: "<div class='ui-grid-cell-contents'>{{grid.appScope.getTasksStatusLabel(row.entity.status)}}</div>"
      }, {
        displayName: Trans.TASKS_LIST_FIELD_DESCRIPTION,
        field: "description"
      }, {
        displayName: Trans.TASKS_LIST_FIELD_OUTPUT,
        field: "output",
        enableSorting: false,
        exporterSuppressExport: true,
        cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
        '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.dialogsService.textareaDialog(row.entity.updated, COL_FIELD, grid.appScope.getTasksStatusLabel(row.entity.status), true)"></i>' +
        "</div>"
      }
    ]
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
        cellTemplate: "<div class='ui-grid-cell-contents'>{{grid.appScope.getTasksStatusLabel(row.entity.status)}}</div>"
      }, {
        displayName: Trans.TASKS_LIST_FIELD_DESCRIPTION,
        field: "description"
      }, {
        displayName: Trans.TASKS_LIST_FIELD_OUTPUT,
        field: "output",
        enableSorting: false,
        exporterSuppressExport: true,
        cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
        '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.dialogsService.textareaDialog(row.entity.updated, COL_FIELD, grid.appScope.getTasksStatusLabel(row.entity.status), true)"></i>' +
        "</div>"
      }
    ]
  };

  $scope.getTasksTypeLabel = function (id) {
    switch (id) {
      case 0:
        return Trans.TASKS_LIST_FIELD_TYPE_PLATFORM_UPGRADE;
      case 1:
        return Trans.TASKS_LIST_FIELD_TYPE_CONTENT_UPGRADE;
      case 2:
        return Trans.TASKS_LIST_FIELD_TYPE_RESTORE_BACKUP;
      case 3:
        return Trans.TASKS_LIST_FIELD_TYPE_BACKUP;
      case 4:
        return Trans.TASKS_LIST_FIELD_TYPE_R_PACKAGE_INSTALL;
    }
  };

  $scope.getTasksStatusLabel = function (id) {
    switch (id) {
      case 0:
        return Trans.TASKS_LIST_FIELD_STATUS_PENDING;
      case 1:
        return Trans.TASKS_LIST_FIELD_STATUS_ONGOING;
      case 2:
        return Trans.TASKS_LIST_FIELD_STATUS_COMPLETED;
      case 3:
        return Trans.TASKS_LIST_FIELD_STATUS_FAILED;
      case 4:
        return Trans.TASKS_LIST_FIELD_STATUS_CANCELED;
    }
  };

  $scope.currentPlatformVersion = null;
  $scope.availablePlatformVersion = null;
  $scope.currentContentVersion = null;
  $scope.availableContentVersion = null;
  $scope.backupPlatformVersion = null;
  $scope.backupContentVersion = null;
  $scope.tasksEnabled = false;

  $scope.isPlatformUpgradePossible = function () {
    var key = "version";
    var cv = key in $scope.internalSettingsMap ? $scope.internalSettingsMap[key] : null;
    var key = "available_platform_version";
    var av = key in $scope.internalSettingsMap ? $scope.internalSettingsMap[key] : null;
    if (av === null)
      return false;
    if (cv === null)
      return true;

    var cvs = cv.split(".");
    var avs = av.split(".");
    for (var i = 0; i < cvs.length && i < avs.length; i++) {
      if ((!isNaN(parseInt(cvs[i])) && !isNaN(parseInt(avs[i])) && parseInt(cvs[i]) > parseInt(avs[i])) || (isNaN(parseInt(avs[i])) && !isNaN(parseInt(cvs[i])))) {
        return false;
      }
      if ((!isNaN(parseInt(cvs[i])) && !isNaN(parseInt(avs[i])) && parseInt(avs[i]) > parseInt(cvs[i])) || (isNaN(parseInt(cvs[i])) && !isNaN(parseInt(avs[i])))) {
        return true;
      }
    }
    return false;
  };

  $scope.upgradePlatform = function () {
    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'task_platform_upgrade_dialog.html',
      controller: TaskUpgradeController,
      size: "lg",
      resolve: {
        changelog: function () {
          return $scope.internalSettingsMap["incremental_platform_changelog"];
        }
      }
    });

    modalInstance.result.then(function (answer) {
      $http.post(Paths.ADMINISTRATION_TASKS_PLATFORM_UPGRADE, {
        backup: answer
      }).then(function (response) {
        $scope.refreshAllTaskRelated();

        if (response.data.result !== 0) {
          $scope.dialogsService.alertDialog(
              Trans.TASKS_DIALOG_TITLE_PLATFORM_UPGRADE_FAILED,
              response.data.result === -1 ? Trans.TASKS_DIALOG_CONTENT_BUSY : response.data.out,
              "danger",
              "lg"
          );
        }
      });
    }, function () {
    });
  };

  $scope.isContentUpgradePossible = function () {
    var key = "incremental_content_changelog";
    var cl = key in $scope.internalSettingsMap ? $scope.internalSettingsMap[key] : null;
    if (!cl || cl.length == 0)
      return false;
    return true;
  };

  $scope.upgradeContent = function () {
    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'task_content_upgrade_dialog.html',
      controller: TaskUpgradeController,
      size: "lg",
      resolve: {
        changelog: function () {
          return $scope.internalSettingsMap["incremental_content_changelog"];
        }
      }
    });

    modalInstance.result.then(function (answer) {
      $http.post(Paths.ADMINISTRATION_TASKS_CONTENT_UPGRADE, {
        backup: answer
      }).then(function (response) {
        $scope.refreshAllTaskRelated();

        if (response.data.result !== 0) {
          $scope.dialogsService.alertDialog(
              Trans.TASKS_DIALOG_TITLE_CONTENT_UPGRADE_FAILED,
              response.data.result === -1 ? Trans.TASKS_DIALOG_CONTENT_BUSY : response.data.out,
              "danger",
              "lg"
          );
        }
      });
    }, function () {
    });
  };

  $scope.backup = function () {
    $scope.dialogsService.confirmDialog(
        Trans.TASKS_DIALOG_TITLE_BACKUP,
        Trans.TASKS_DIALOG_CONFIRM_BACKUP,
        function (response) {
          $http.post(Paths.ADMINISTRATION_TASKS_BACKUP, {}).then(function (response) {
            $scope.refreshAllTaskRelated();

            if (response.data.result !== 0) {
              $scope.dialogsService.alertDialog(
                  Trans.TASKS_DIALOG_TITLE_BACKUP_FAILED,
                  response.data.result === -1 ? Trans.TASKS_DIALOG_CONTENT_BUSY : response.data.out,
                  "danger",
                  "lg"
              );
            }
          });
        }
    );
  };

  $scope.isRestorePossible = function () {
    var key = "backup_platform_version";
    return key in $scope.internalSettingsMap && $scope.internalSettingsMap[key];
  };

  $scope.restore = function () {
    $scope.dialogsService.confirmDialog(
        Trans.TASKS_DIALOG_TITLE_RESTORE,
        Trans.TASKS_DIALOG_CONFIRM_RESTORE,
        function (answer) {
          $http.post(Paths.ADMINISTRATION_TASKS_RESTORE, {}).then(function (response) {
            $scope.refreshAllTaskRelated();

            if (response.data.result !== 0) {
              $scope.dialogsService.alertDialog(
                  Trans.TASKS_DIALOG_TITLE_RESTORE_FAILED,
                  response.data.result === -1 ? Trans.TASKS_DIALOG_CONTENT_BUSY : response.data.out,
                  "danger",
                  "lg"
              );
            }
          });
        }
    );
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
          $http.post($scope.deleteApiClientsPath.pf(ids), {}).success(function (data) {
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
          $http.post($scope.clearApiClientsPath, {}).success(function (data) {
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
              "danger")
          break;
      }
    });
  };

  $scope.installPackage = function () {
    var modalInstance = $uibModal.open({
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
              response.data.result === -1 ? Trans.TASKS_DIALOG_CONTENT_BUSY : response.data.out,
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
      $scope.tasksCollection = ScheduledTasksCollectionService.collection;
      $scope.packagesTasksCollection = ScheduledTasksCollectionService.packagesCollection;
    });
  };

  $scope.refreshSettings = function () {
    AdministrationSettingsService.fetchSettingsMap(null, function () {
      $scope.exposedSettingsMap = AdministrationSettingsService.exposedSettingsMap;
      $scope.internalSettingsMap = AdministrationSettingsService.internalSettingsMap;

      var key = "version";
      $scope.currentPlatformVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "installed_content_version";
      $scope.currentContentVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "available_platform_version";
      $scope.availablePlatformVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "available_content_version";
      $scope.availableContentVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "backup_platform_version";
      $scope.backupPlatformVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "backup_content_version";
      $scope.backupContentVersion = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] ? $scope.internalSettingsMap[key] : Trans.ADMINISTRATION_VERSION_NONE;

      var key = "scheduled_tasks";
      $scope.tasksEnabled = key in $scope.internalSettingsMap && $scope.internalSettingsMap[key] === "1";
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

concertoPanel.controller('AdministrationController', ["$scope", "$http", "$uibModal", "AdministrationSettingsService", "SessionCountCollectionService", "uiGridConstants", "MessagesCollectionService", "ScheduledTasksCollectionService", "ApiClientsCollectionService", "DialogsService", AdministrationController]);
