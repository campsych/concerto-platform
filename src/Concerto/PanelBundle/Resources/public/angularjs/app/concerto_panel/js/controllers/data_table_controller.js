function DataTableController($scope, $uibModal, $http, $filter, $timeout, $state, $sce, uiGridConstants, GridService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService) {
    $scope.tabStateName = "tables";
    $scope.tabIndex = 2;
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DataTableCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService);
    $scope.exportable = true;
    $scope.deletePath = Paths.DATA_TABLE_DELETE;
    $scope.addFormPath = Paths.DATA_TABLE_ADD_FORM;
    $scope.fetchObjectPath = Paths.DATA_TABLE_FETCH_OBJECT;
    $scope.savePath = Paths.DATA_TABLE_SAVE;
    $scope.importPath = Paths.DATA_TABLE_IMPORT;
    $scope.saveNewPath = Paths.DATA_TABLE_SAVE_NEW;
    $scope.exportPath = Paths.DATA_TABLE_EXPORT;
    $scope.columnsCollectionPath = Paths.DATA_TABLE_COLUMNS_COLLECTION;
    $scope.deleteColumnPath = Paths.DATA_TABLE_COLUMNS_DELETE;
    $scope.fetchColumnObjectPath = Paths.DATA_TABLE_COLUMNS_FETCH_OBJECT;
    $scope.dataCollectionPath = Paths.DATA_TABLE_DATA_COLLECTION;
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
    $scope.$on('ngGridEventEndCellEdit', function (data) {
        $scope.saveRow(data.targetScope.row.entity);
    });
    $scope.datePickerOptions = {
    };
    $scope.datePickerFormat = "yyyy-MM-dd";
    $scope.$watchCollection("object.columns", function (newStructure) {
        $scope.dataOptions.columnDefs = [];
        if (newStructure == null)
            return;
        for (var i = 0; i < newStructure.length; i++) {
            var col = newStructure[i];
            var colDef = {
                field: col.name,
                displayName: col.name,
                enableCellEdit: col.name !== "id"
            };
            switch (col.type) {
                case "boolean":
                    colDef.cellTemplate =
                            "<div lass='ui-grid-cell-contents' align='center'>" +
                            "<input ng-disabled='grid.appScope.object.initProtected == \"1\"' type='checkbox' ng-change='grid.appScope.saveRow(row.entity)' ng-model='row.entity." + col.name + "' ng-true-value='\"1\"' ng-false-value='\"0\"' />" +
                            "</div>";
                    colDef.enableCellEdit = false;
                    break;
                case "date":
                    colDef.cellTemplate = "<div class='ui-grid-cell-contents' align='center'>" +
                            "<input ng-disabled='grid.appScope.object.initProtected == \"1\"' type='text' ng-click='row.entity._datepicker_opened=true' ng-model='row.entity." + col.name + "' " +
                            "datepicker-append-to-body='true' ng-readonly='true' ng-change='grid.appScope.saveRow(row.entity)' style='width:100%;' " +
                            "datepicker-options='grid.appScope.datePickerOptions' is-open='row.entity._datepicker_opened' datepicker-popup='{{datePickerFormat}}' />" +
                            "</div>";
                    colDef.enableCellEdit = false;
                    break;
                case "text":
                    colDef.cellTemplate = "<div class='ui-grid-cell-contents' align='center'>" +
                            '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.editTextCell(row.entity, \'' + col.name + '\')" uib-tooltip="row.entity.' + col.name + '" tooltip-append-to-body="true"></i>' +
                            "</div>";
                    colDef.enableCellEdit = false;
                    break;
            }

            $scope.dataOptions.columnDefs.push(colDef);
        }
        $scope.dataOptions.columnDefs.push({
            cellTemplate: "<div class='ui-grid-cell-contents' align='center'>" +
                    "<button ng-disabled='grid.appScope.object.initProtected == \"1\"' class='btn btn-danger btn-xs' ng-click='grid.appScope.deleteRow(row.entity.id);'>" +
                    Trans.DATA_TABLE_DATA_LIST_DELETE +
                    "</button>",
            width: 60,
            enableCellEdit: false,
            displayName: "",
            name: "_action",
            enableSorting: false,
            enableFiltering: false,
            exporterSuppressExport: true,
        });
        $scope.fetchDataCollection($scope.object.id);
        $scope.structureOptions.enableFiltering = $scope.object.columns.length > 0;
        if ($scope.structureGridApi && uiGridConstants.dataChange) {
            $scope.structureGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });
    $scope.editTextCell = function (entity, colName) {
        if ($scope.object.initProtected === '1')
            return;
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "ckeditor_dialog.html",
            controller: CKEditorController,
            resolve: {
                title: function () {
                    return Trans.DATA_TABLE_CELL_TEXT_EDIT_TITLE;
                },
                tooltip: function () {
                    return Trans.DATA_TABLE_CELL_TEXT_EDIT_TOOLTIP;
                },
                value: function () {
                    return entity[colName];
                }
            },
            size: "lg"
        });
        modalInstance.result.then(function (newVal) {
            entity[colName] = newVal;
            $scope.saveRow(entity);
        }, function () {
        });
    };
    $scope.structureOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "object.columns",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
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
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-default btn-xs" ng-click="grid.appScope.editStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_EDIT + '</button>' +
                        '<button ng-disabled="grid.appScope.object.initProtected == \'1\'" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_DELETE + '</button>' +
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
    $scope.dataOptions = {
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: 'data',
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        columnDefs: [],
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
        enableCellEditOnFocus: $scope.object.initProtected !== "1"
    };
    $scope.$watch("data.length", function (newValue) {
        $scope.dataOptions.enableFiltering = newValue > 0;
        if ($scope.dataGridApi && uiGridConstants.dataChange) {
            $scope.dataGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
    });
    $scope.refreshRows = function () {
        $scope.fetchDataCollection($scope.object.id);
    };
    $scope.addRow = function () {
        $http.post($scope.dataInsertPath.pf($scope.object.id)).success(function (response) {
            $scope.fetchDataCollection($scope.object.id);
        });
    };
    $scope.saveRow = function (row) {
        for (var key in row) {
            if (key.substring(0, 1) === "$" || key.substring(0, 1) === "_")
                delete row[key];
            if (row[key] instanceof Date) {
                row[key] = $filter('date')(row[key], "yyyy-MM-dd");
            }
        }

        $http.post($scope.dataUpdatePath.pf($scope.object.id, row.id), {
            values: row
        }).success(function (response) {
        });
    };
    $scope.deleteAllRows = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE;
                }
            }
        });
        modalInstance.result.then(function (response) {
            $http.post($scope.truncatePath.pf($scope.object.id)).success(function (data) {
                $scope.fetchDataCollection($scope.object.id);
            });
        }, function () {
        });
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

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE;
                }
            }
        });
        modalInstance.result.then(function (response) {
            $http.post($scope.deleteDataPath.pf($scope.object.id, ids), {
            }).success(function (data) {
                $scope.fetchDataCollection($scope.object.id);
            });
        }, function () {
        });
    };
    $scope.fetchDataCollection = function (tableId) {
        $scope.dataGridApi.selection.clearSelectedRows();
        $http.post($scope.dataCollectionPath.pf(tableId), {
            filters: angular.toJson($scope.dataFilterOptions)
        }).success(function (collection) {
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

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.DATA_TABLE_STRUCTURE_DIALOG_MESSAGE_CONFIRM_DELETE;
                }
            }
        });
        modalInstance.result.then(function (response) {
            $http.post($scope.deleteColumnPath.pf($scope.object.id, names), {
            }).success(function (data) {
                $scope.fetchObjectCollection();
            });
        }, function () {
        });
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
            $scope.fetchObjectCollection();
        }, function () {
        });
    };
    $scope.importCsv = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "import_csv_dialog.html",
            controller: DataTableImportCsvController,
            size: "lg",
            resolve: {
                object: function () {
                    return $scope.object;
                }
            }
        });
        modalInstance.result.then(function (response) {
            $scope.fetchObjectCollection();
        }, function () {
        });
    };
    $scope.onObjectChanged = function (newObject, oldObject) {
        $scope.super.onObjectChanged(newObject, oldObject);
        if ($scope.structureGridApi)
            $scope.structureGridApi.selection.clearSelectedRows();
        $scope.dataFilterOptions.filters = {};
        $scope.dataFilterOptions.sorting = [];
    };
    $scope.resetObject = function () {
        $scope.object = {
            id: 0,
            name: "",
            accessibility: 0,
            description: ""
        };
    };
    $scope.resetObject();
    $scope.initializeColumnDefs();
    $scope.fetchObjectCollection();
}

concertoPanel.controller('DataTableController', ["$scope", "$uibModal", "$http", "$filter", "$timeout", "$state", "$sce", "uiGridConstants", "GridService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", DataTableController]);
