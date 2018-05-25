function DataTableController($scope, $uibModal, $http, $filter, $timeout, $state, $sce, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService) {
  $scope.tabStateName = "tables";
  BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, DataTableCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService);
  $scope.exportable = true;
  $scope.deletePath = Paths.DATA_TABLE_DELETE;
  $scope.addFormPath = Paths.DATA_TABLE_ADD_FORM;
  $scope.fetchObjectPath = Paths.DATA_TABLE_FETCH_OBJECT;
  $scope.savePath = Paths.DATA_TABLE_SAVE;
  $scope.importPath = Paths.DATA_TABLE_IMPORT;
  $scope.preImportStatusPath = Paths.DATA_TABLE_PRE_IMPORT_STATUS;
  $scope.saveNewPath = Paths.DATA_TABLE_SAVE_NEW;
  $scope.exportPath = Paths.DATA_TABLE_EXPORT;
  $scope.columnsCollectionPath = Paths.DATA_TABLE_COLUMNS_COLLECTION;
  $scope.deleteColumnPath = Paths.DATA_TABLE_COLUMNS_DELETE;
  $scope.fetchColumnObjectPath = Paths.DATA_TABLE_COLUMNS_FETCH_OBJECT;
  $scope.dataCollectionPath = Paths.DATA_TABLE_DATA_COLLECTION;
  $scope.dataAllCsvPath = Paths.DATA_TABLE_DATA_ALL_CSV;
  $scope.dataUpdatePath = Paths.DATA_TABLE_DATA_UPDATE;
  $scope.dataInsertPath = Paths.DATA_TABLE_DATA_INSERT;
  $scope.deleteDataPath = Paths.DATA_TABLE_DATA_DELETE;
  $scope.truncatePath = Paths.DATA_TABLE_DATA_TRUNCATE;
  $scope.formTitleAddLabel = Trans.DATA_TABLE_FORM_TITLE_ADD;
  $scope.formTitleEditLabel = Trans.DATA_TABLE_FORM_TITLE_EDIT;
  $scope.formTitle = $scope.formTitleAddLabel;
  $scope.tabAccordion.data = {
    open: true
  };
  $scope.additionalColumnsDef = [
    {
      displayName: Trans.DATA_TABLE_LIST_FIELD_NAME,
      field: "name"
    }
  ];
  $scope.column = {
    type: "string"
  };
  $scope.data = [];
  $scope.structure = [];
  $scope.$on('ngGridEventEndCellEdit', function (data) {
    $scope.saveRow(data.targetScope.row.entity);
  });
  $scope.datePickerOptions = {};
  $scope.datePickerFormat = "yyyy-MM-dd";
  $scope.$watchCollection("object.columns", function (newStructure) {
    $scope.dataOptions.columnDefs = [];
    $scope.columns = newStructure;
    if (newStructure == null)
      return;
    for (var i = 0; i < newStructure.length; i++) {
      var col = newStructure[i];
      var colDef = {
        field: col.name,
        displayName: col.name,
        enableCellEdit: col.name !== "id",
        type: "string"
      };
      switch (col.type) {
        case "boolean":
          colDef.cellTemplate =
              "<div lass='ui-grid-cell-contents' align='center'>" +
              "<input type='checkbox' ng-change='grid.appScope.saveRow(row.entity)' ng-model='row.entity." + col.name + "' ng-true-value='\"1\"' ng-false-value='\"0\"' />" +
              "</div>";
          colDef.enableCellEdit = false;
          break;
        case "date":
          colDef.cellTemplate = "<div class='ui-grid-cell-contents' align='center'>" +
              "<input type='text' ng-click='row.entity._datepicker_opened=true' ng-model='row.entity." + col.name + "' " +
              "datepicker-append-to-body='true' ng-readonly='true' ng-change='grid.appScope.saveRow(row.entity)' style='width:100%;' " +
              "datepicker-options='grid.appScope.datePickerOptions' is-open='row.entity._datepicker_opened' uib-datepicker-popup='{{grid.appScope.datePickerFormat}}' class='form-control' />" +
              "</div>";
          colDef.enableCellEdit = false;
          colDef.type = "date";
          break;
        case "text":
          colDef.cellTemplate = "<div class='ui-grid-cell-contents' align='center'>" +
              '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.editTextCell(row.entity, \'' + col.name + '\')" uib-tooltip="{{row.entity.' + col.name + '}}" tooltip-append-to-body="true"></i>' +
              "</div>";
          colDef.enableCellEdit = false;
          break;
      }

      $scope.dataOptions.columnDefs.push(colDef);
    }
    $scope.dataOptions.columnDefs.push({
      cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
      "<button class='btn btn-danger btn-xs' ng-click='grid.appScope.deleteRow(row.entity.id);'>" +
      Trans.DATA_TABLE_DATA_LIST_DELETE +
      "</button>",
      width: 60,
      enableCellEdit: false,
      displayName: "",
      name: "_action",
      enableSorting: false,
      enableFiltering: false,
      exporterSuppressExport: true
    });
    $scope.fetchDataCollection($scope.object.id);
  });
  $scope.editTextCell = function (entity, colName) {
    $scope.dialogsService.ckeditorDialog(
        Trans.DATA_TABLE_CELL_TEXT_EDIT_TITLE,
        Trans.DATA_TABLE_CELL_TEXT_EDIT_TOOLTIP,
        entity[colName],
        function (newVal) {
          entity[colName] = newVal;
          $scope.saveRow(entity);
        }
    );
  };
  $scope.structureOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    data: "columns",
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.structureOptions.enableFiltering = !$scope.structureOptions.enableFiltering;
          $scope.structureGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    columnDefs: [
      {
        displayName: Trans.DATA_TABLE_STRUCTURE_LIST_FIELD_NAME,
        field: "name"
      }, {
        displayName: Trans.DATA_TABLE_STRUCTURE_LIST_FIELD_TYPE,
        field: "type"
      }, {
        displayName: "",
        name: "_action",
        enableSorting: false,
        enableFiltering: false,
        exporterSuppressExport: true,
        cellTemplate:
        "<div class='ui-grid-cell-contents' align='center'>" +
        '<button ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" class="btn btn-default btn-xs" ng-click="grid.appScope.editStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_EDIT + '</button>' +
        '<button ng-disabled="grid.appScope.object.starterContent && !grid.appScope.administrationSettingsService.starterContentEditable" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.structureGridApi = gridApi;
      gridApi.selection.on.rowSelectionChanged($scope, function (row) {
        if (row.entity.name === "id") {
          gridApi.selection.unSelectRow(row.entity);
        }
      });
      gridApi.selection.on.rowSelectionChangedBatch($scope, function (rows) {
        for (var i = 0; i < rows.length; i++) {
          var row = rows[i];
          if (row.entity.name === "id") {
            gridApi.selection.unSelectRow(row.entity);
          }
        }
      });
    }
  };
  $scope.dataFilterOptions = {
    filters: {},
    sorting: [],
    paging: {
      page: 1,
      pageSize: 500
    }
  };

  $scope.downloadDataList = function () {
    $scope.dataOptions.exporterCsvFilename = $scope.object.name + ".csv";

    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'download_list_dialog.html',
      controller: DownloadListController,
      size: "lg"
    });
    modalInstance.result.then(function (options) {
      if (options.format === 'csv') {
        if (options.rows === "all") {
          window.open($scope.dataAllCsvPath.pf($scope.object.id, $scope.object.name + ".csv"), "_blank");
        } else {
          var elem = angular.element(document.querySelectorAll(".custom-csv-link-location"));
          $scope.dataGridApi.exporter.csvExport(options.rows, options.cols, elem);
        }
      } else if (options.format === 'pdf') {
        $scope.dataGridApi.exporter.pdfExport(options.rows, options.cols);
      }
    });
  };

  $scope.dataOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    data: 'data',
    exporterCsvFilename: "export.csv",
    showGridFooter: true,
    columnDefs: [],
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.dataOptions.enableFiltering = !$scope.dataOptions.enableFiltering;
          $scope.dataGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.dataGridApi = gridApi;
      $scope.dataGridApi.core.on.filterChanged($scope, function () {
        var grid = this.grid;
        if ($scope.filterTimeout) {
          $timeout.cancel($scope.filterTimeout);
          $scope.filterTimeout = false;
        }
        $scope.filterTimeout = $timeout(function () {
          for (var i = 1; i < grid.columns.length - 1; i++) {
            var column = grid.columns[i];
            $scope.dataFilterOptions.filters[column.name] = column.filters[0].term;
          }
          $scope.fetchDataCollection($scope.object.id);
        }, 500);
      });
      $scope.dataGridApi.core.on.sortChanged($scope, function (grid, sortColumns) {
        $scope.dataFilterOptions.sorting = [];
        for (var i = 0; i < sortColumns.length; i++) {
          $scope.dataFilterOptions.sorting.push({
            name: sortColumns[i].name,
            dir: sortColumns[i].sort.direction
          });
        }
        $scope.fetchDataCollection($scope.object.id);
      });
      $scope.dataGridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
        $scope.dataFilterOptions.paging.page = newPage;
        $scope.dataFilterOptions.paging.pageSize = pageSize;
        $scope.fetchDataCollection($scope.object.id);
      });
      $scope.dataGridApi.edit.on.afterCellEdit($scope, function (rowEntity, colDef, newValue, oldValue) {
        $scope.saveRow(rowEntity);
      });
    },
    exporterAllDataFn: function () {
      return $http.get($scope.dataCollectionPath.pf($scope.object.id)).success(function (data) {
        $scope.dataOptions.data = data.content;
      });
    },
    paginationPageSizes: [100, 250, 500],
    paginationPageSize: $scope.dataFilterOptions.paging.pageSize,
    useExternalPagination: true,
    useExternalSorting: true,
    useExternalFiltering: true,
    enableCellEditOnFocus: true
  };
  $scope.refreshRows = function () {
    $scope.fetchDataCollection($scope.object.id);
  };
  $scope.addRow = function () {
    $http.post($scope.dataInsertPath.pf($scope.object.id)).success(function (response) {
      $scope.fetchDataCollection($scope.object.id);
    });
  };
  $scope.saveRow = function (row) {
    var newRow = angular.copy(row);
    for (var key in newRow) {
      if (key.substring(0, 1) === "$" || key.substring(0, 1) === "_")
        delete newRow[key];
      if (newRow[key] instanceof Date) {
        newRow[key] = $filter('date')(newRow[key], "yyyy-MM-dd");
      }
    }

    $http.post($scope.dataUpdatePath.pf($scope.object.id, newRow.id), {
      values: newRow
    }).then(function (response) {
    }).catch(function (error) {
      $scope.refreshRows();
    });
  };

  $scope.deleteAllRows = function () {
    $scope.dialogsService.confirmDialog(
        Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
        Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE,
        function (response) {
          $http.post($scope.truncatePath.pf($scope.object.id)).success(function (data) {
            $scope.fetchDataCollection($scope.object.id);
          });
        }
    );
  };

  $scope.deleteSelectedRows = function () {
    var ids = [];
    for (var i = 0; i < $scope.dataGridApi.selection.getSelectedRows().length; i++) {
      ids.push($scope.dataGridApi.selection.getSelectedRows()[i].id);
    }
    $scope.deleteRow(ids);
  };

  $scope.deleteRow = function (ids) {
    if (!(ids instanceof Array)) {
      ids = [ids];
    }

    $scope.dialogsService.confirmDialog(
        Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
        Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE,
        function (response) {
          $http.post($scope.deleteDataPath.pf($scope.object.id, ids), {}).success(function (data) {
            $scope.fetchDataCollection($scope.object.id);
          });
        }
    );
  };

  $scope.fetchDataCollection = function (tableId) {
    if ($scope.dataGridApi) {
      $scope.dataGridApi.selection.clearSelectedRows();
    }

    if (tableId === 0) {
      $scope.data = [];
      $scope.dataOptions.totalItems = 0;
      return;
    }

    $http.post($scope.dataCollectionPath.pf(tableId), {
      filters: angular.toJson($scope.dataFilterOptions)
    }).success(function (collection) {
      for (var i = 0; i < $scope.object.columns.length; i++) {
        var col = $scope.object.columns[i];
        if (col.type == "date") {
          for (var j = 0; j < collection.content.length; j++) {
            var row = collection.content[j];
            if (!(row[col.name] instanceof Date)) {
              row[col.name] = new Date(row[col.name]);
            }
            if (isNaN(row[col.name].getTime())) {
              row[col.name] = new Date(0);
            }
          }
        }
      }

      $scope.data = collection.content;
      $scope.dataOptions.totalItems = collection.count;
    });
  };
  $scope.deleteSelectedStructure = function () {
    var names = [];
    for (var i = 0; i < $scope.structureGridApi.selection.getSelectedRows().length; i++) {
      names.push($scope.structureGridApi.selection.getSelectedRows()[i].name);
    }
    $scope.deleteStructure(names);
  };

  $scope.deleteStructure = function (names) {
    if (!(names instanceof Array)) {
      names = [names];
    }

    $scope.dialogsService.confirmDialog(
        Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_DELETE,
        Trans.DATA_TABLE_STRUCTURE_DIALOG_MESSAGE_CONFIRM_DELETE,
        function (response) {
          $http.post($scope.deleteColumnPath.pf($scope.object.id, names), {}).success(function (data) {
            $scope.setWorkingCopyObject();
            $scope.fetchObjectCollection();
          });
        }
    );
  };

  $scope.fetchColumn = function (id, column_name, callback) {
    $http.get($scope.fetchColumnObjectPath.pf(id, column_name)).success(function (object) {
      if (object !== null) {
        $scope.column = object;
        if (callback != null) {
          callback.call(this);
        }
      }
    });
  };
  $scope.addStructure = function () {
    $scope.column = {
      id: 0,
      name: "",
      type: "string"
    };
    $scope.launchStructureDialog($scope.column);
  };
  $scope.editStructure = function (name) {
    $scope.fetchColumn($scope.object.id, name, function () {
      $scope.launchStructureDialog($scope.column);
    });
  };

  $scope.launchStructureDialog = function (column) {
    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "structure_dialog.html",
      controller: DataTableStructureSaveController,
      scope: $scope,
      resolve: {
        table: function () {
          return $scope.object;
        },
        object: function () {
          return $scope.column;
        }
      },
      size: "lg"
    });
    modalInstance.result.then(function (result) {
      $scope.setWorkingCopyObject();
      $scope.fetchObjectCollection();
    }, function () {
    });
  };
  $scope.importCsv = function () {
    var modalInstance = $uibModal.open({
      templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "import_csv_dialog.html",
      controller: DataTableImportCsvController,
      size: "lg",
      backdrop: 'static',
      keyboard: false,
      resolve: {
        object: function () {
          return $scope.object;
        },
        editable: function () {
          return !$scope.object.starterContent || $scope.administrationSettingsService.starterContentEditable;
        }
      }
    });
    modalInstance.result.then(function (response) {
      $scope.setWorkingCopyObject();
      $scope.fetchObjectCollection();
    }, function (dirty) {
      if (dirty === true) {
        $scope.setWorkingCopyObject();
        $scope.fetchObjectCollection();
      }
    });
  };

  $scope.onObjectChanged = function () {
    $scope.super.onObjectChanged();
    if ($scope.structureGridApi)
      $scope.structureGridApi.selection.clearSelectedRows();
    $scope.dataFilterOptions.filters = {};
    $scope.dataFilterOptions.sorting = [];
  };

  $scope.onAfterPersist = function () {
    $scope.testCollectionService.fetchObjectCollection();
    $scope.testWizardCollectionService.fetchObjectCollection();
  };

  $scope.resetObject = function () {
    $scope.object = {
      id: 0,
      name: "",
      accessibility: 0,
      description: "",
      columns: []
    };
  };
  $scope.resetObject();
  $scope.initializeColumnDefs();
}

concertoPanel.controller('DataTableController', ["$scope", "$uibModal", "$http", "$filter", "$timeout", "$state", "$sce", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "AdministrationSettingsService", DataTableController]);
