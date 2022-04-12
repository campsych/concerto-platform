function DataTableController($scope, $uibModal, $http, $filter, $timeout, $state, $sce, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService) {
    $scope.tabStateName = "tables";
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, DataTableCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService);
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
    $scope.fetchColumnObjectPath = Paths.DATA_TABLE_COLUMNS_FETCH_OBJECT;
    $scope.dataCollectionPath = Paths.DATA_TABLE_DATA_COLLECTION;
    $scope.dataAllCsvPath = Paths.DATA_TABLE_DATA_ALL_CSV;
    $scope.exportInstructionsPath = Paths.DATA_TABLE_EXPORT_INSTRUCTIONS;
    $scope.lockPath = Paths.DATA_TABLE_LOCK;

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
    $scope.datePickerOptions = {
        autoclose: true
    };
    $scope.datePickerFormat = "yyyy-MM-dd";

    $scope.toggleFieldNull = function (row, fieldName) {
        row[fieldName] = row[fieldName] === null ? $scope.getDefaultColumnValue(fieldName) : null;
        $scope.saveRow(row, fieldName);
    };

    $scope.getDefaultColumnValue = function (fieldName) {
        //no nulls
        console.log(fieldName);

        let col = null;
        for (let i = 0; i < $scope.columns.length; i++) {
            if (fieldName === $scope.columns[i].name) {
                col = $scope.columns[i];
            }
        }
        if (col === null) return null;

        switch (col.type) {
            case "boolean":
                return 0;
            case "date":
                return new Date(0);
            case "datetime":
                return "1970-01-01 00:00:00";
            default:
                return "";
        }
    };

    $scope.$watchCollection("object.columns", function (newStructure, oldStructure) {
        $scope.dataOptions.columnDefs = [];
        $scope.columns = newStructure;
        if (!newStructure || newStructure.length === 0)
            return;

        for (let i = 0; i < newStructure.length; i++) {
            let col = newStructure[i];
            let colDef = {
                field: col.name,
                displayName: col.name,
                enableCellEdit: col.name !== "id",
                type: "string",
                minWidth: 150
            };

            if (col.name === "id") {
                colDef.minWidth = 75;
            }

            let nullableCb = "";
            if (col.nullable) {
                nullableCb = "<input type='checkbox' class='nullable-checkbox' ng-model='row.entity." + col.name + "' ng-true-value='null' ng-click='grid.appScope.toggleFieldNull(row.entity, \"" + col.name + "\")' uib-tooltip='NULL' tooltip-append-to-body='true' />";
            }

            switch (col.type) {
                case "boolean":
                    colDef.cellTemplate =
                        "<div class='ui-grid-cell-contents' align='center' ng-class='{\"ui-grid-cell-contents-null\": COL_FIELD === null}'>" +
                        "<input type='checkbox' ng-change='grid.appScope.saveRow(row.entity, \"" + col.name + "\")' ng-model='row.entity." + col.name + "' ng-true-value='\"1\"' ng-false-value='\"0\"' style='margin: 0;' />" +
                        nullableCb +
                        "</div>";
                    colDef.enableCellEdit = false;
                    break;
                case "date":
                    colDef.cellTemplate = "<div class='ui-grid-cell-contents' ng-class='{\"ui-grid-cell-contents-null\": COL_FIELD === null}'>" +
                        "<input type='text' ng-click='row.entity._datepicker_" + col.name + "_opened=true' ng-model='row.entity." + col.name + "' " +
                        "datepicker-append-to-body='true' ng-readonly='true' ng-change='grid.appScope.saveRow(row.entity, \"" + col.name + "\")' style='width:100%;' " +
                        "datepicker-options='grid.appScope.datePickerOptions' is-open='row.entity._datepicker_" + col.name + "_opened' uib-datepicker-popup='{{grid.appScope.datePickerFormat}}' class='form-control' />" +
                        nullableCb +
                        "</div>";
                    colDef.enableCellEdit = false;
                    colDef.type = "date";
                    break;
                case "json":
                case "text":
                    colDef.cellTemplate = "<div class='ui-grid-cell-contents' align='center' ng-class='{\"ui-grid-cell-contents-null\": COL_FIELD === null}'>" +
                        '<i class="glyphicon glyphicon-align-justify clickable" ng-click="grid.appScope.editTextCell(row.entity, \'' + col.name + '\')" uib-tooltip="{{row.entity.' + col.name + '}}" tooltip-append-to-body="true"></i>' +
                        nullableCb +
                        "</div>";
                    colDef.enableCellEdit = false;
                    break;
                default:
                    colDef.cellTemplate = "<div class='ui-grid-cell-contents' ng-class='{\"ui-grid-cell-contents-null\": COL_FIELD === null}'>" +
                        "{{COL_FIELD}}" +
                        nullableCb +
                        "</div>";
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

        if (newStructure.length === oldStructure.length) {
            let same = true;
            for (let i = 0; i < newStructure.length; i++) {
                if (
                    newStructure[i].name !== oldStructure[i].name ||
                    newStructure[i].length !== oldStructure[i].length
                ) {
                    same = false;
                    break;
                }
            }
            if (same) return;
        }

        $scope.fetchDataCollection($scope.object.id);
    });

    $scope.editTextCell = function (entity, colName) {
        DialogsService.textareaDialog(
            Trans.DATA_TABLE_CELL_TEXT_EDIT_TITLE,
            entity[colName],
            Trans.DATA_TABLE_CELL_TEXT_EDIT_TOOLTIP,
            !$scope.isEditable(),
            function (newVal) {
                entity[colName] = newVal;
                $scope.saveRow(entity, colName);
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
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
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
                displayName: Trans.DATA_TABLE_STRUCTURE_LIST_FIELD_LENGTH,
                field: "length"
            }, {
                displayName: Trans.DATA_TABLE_STRUCTURE_LIST_FIELD_NULLABLE,
                field: "nullable",
                cellTemplate:
                    "<div class='ui-grid-cell-contents' align='center'>" +
                    "<i class='glyphicon glyphicon-{{COL_FIELD ? \"ok\" : \"remove\"}}'></i>" +
                    "</div>"
            }, {
                displayName: "",
                name: "_action",
                enableSorting: false,
                enableFiltering: false,
                exporterSuppressExport: true,
                cellTemplate:
                    "<div class='ui-grid-cell-contents' align='center'>" +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-default btn-xs" ng-click="grid.appScope.editStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_EDIT + '</button>' +
                    '<button ng-disabled="!grid.appScope.isEditable()" class="btn btn-danger btn-xs" ng-click="grid.appScope.deleteStructure(row.entity.name);" ng-show="row.entity.name!=\'id\'">' + Trans.DATA_TABLE_STRUCTURE_LIST_DELETE + '</button>' +
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
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
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
                if (newValue !== oldValue) $scope.saveRow(rowEntity, colDef.name);
            });
            $scope.dataGridApi.pagination.seek(1);
        },
        exporterAllDataFn: function () {
            return $http.get($scope.dataCollectionPath.pf($scope.object.id)).then(function (httpResponse) {
                $scope.dataOptions.data = httpResponse.data.content;
            });
        },
        paginationPageSizes: [100, 250, 500],
        paginationPageSize: $scope.dataFilterOptions.paging.pageSize,
        useExternalPagination: true,
        useExternalSorting: true,
        useExternalFiltering: true,
        enableCellEditOnFocus: false
    };

    $scope.addRow = function () {
        $http.post(Paths.DATA_TABLE_DATA_INSERT.pf($scope.object.id), {
            objectTimestamp: $scope.object.updatedOn
        }).then(function (httpResponse) {
            if (httpResponse.data.result == 0) {
                $scope.object.updatedOn = httpResponse.data.objectTimestamp;
                $scope.fetchDataCollection($scope.object.id);
            } else {
                DialogsService.alertDialog(
                    Trans.DATA_TABLE_DATA_DIALOG_TITLE_EDIT,
                    httpResponse.data.errors.join("<br/>"),
                    "danger"
                );
            }
        });
    };
    $scope.saveRow = function (row, fieldName = null) {
        let newRow = angular.copy(row);

        /* commented out individual field update as api will null out nullable fields when missing in update params
        if (fieldName !== null) {
            newRow = {};
            newRow.id = row.id;
            newRow[fieldName] = row[fieldName];
        }
        */

        for (let key in newRow) {
            if (key.substring(0, 1) === "$" || key.substring(0, 1) === "_")
                delete newRow[key];
            if (newRow[key] instanceof Date) {
                newRow[key] = $filter('date')(newRow[key], "yyyy-MM-dd");
            }
        }

        $http.post(Paths.DATA_TABLE_DATA_UPDATE.pf($scope.object.id, newRow.id), {
            values: newRow,
            objectTimestamp: $scope.object.updatedOn
        }).then(function (httpResponse) {
            switch (httpResponse.data.result) {
                case BaseController.RESULT_OK: {
                    $scope.object.updatedOn = httpResponse.data.objectTimestamp;
                    break;
                }
                case BaseController.RESULT_VALIDATION_FAILED: {
                    DialogsService.alertDialog(
                        Trans.DATA_TABLE_DATA_DIALOG_TITLE_EDIT,
                        httpResponse.data.errors.join("<br/>"),
                        "danger"
                    );
                    break;
                }
            }
        }).catch(function (error) {
            console.log(error);
            DialogsService.alertDialog(
                Trans.DATA_TABLE_DATA_DIALOG_TITLE_EDIT,
                Trans.DIALOG_MESSAGE_FAILED,
                "danger"
            );
            $scope.fetchDataCollection($scope.object.id);
        });
    };

    $scope.deleteAllRows = function () {
        DialogsService.confirmDialog(
            Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
            Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE,
            function (response) {
                $http.post(Paths.DATA_TABLE_DATA_DELETE_ALL.pf($scope.object.id), {
                    objectTimestamp: $scope.object.updatedOn
                }).then(function (httpResponse) {
                    switch (httpResponse.data.result) {
                        case BaseController.RESULT_OK: {
                            $scope.object.updatedOn = httpResponse.data.objectTimestamp;
                            $scope.fetchDataCollection($scope.object.id);
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED: {
                            $scope.dialogsService.alertDialog(
                                Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
                                httpResponse.data.errors.join("<br/>"),
                                "danger"
                            );
                            break;
                        }
                    }
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

        DialogsService.confirmDialog(
            Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
            Trans.DATA_TABLE_DATA_DIALOG_MESSAGE_CONFIRM_DELETE,
            function (response) {
                $http.post(Paths.DATA_TABLE_DATA_DELETE.pf($scope.object.id, ids), {
                    objectTimestamp: $scope.object.updatedOn
                }).then(function (httpResponse) {
                    switch (httpResponse.data.result) {
                        case BaseController.RESULT_OK: {
                            $scope.object.updatedOn = httpResponse.data.objectTimestamp;
                            $scope.fetchDataCollection($scope.object.id);
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED: {
                            $scope.dialogsService.alertDialog(
                                Trans.DATA_TABLE_DATA_DIALOG_TITLE_DELETE,
                                httpResponse.data.errors.join("<br/>"),
                                "danger"
                            );
                            break;
                        }
                    }
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
        }).then(function (httpResponse) {
            for (var i = 0; i < $scope.object.columns.length; i++) {
                var col = $scope.object.columns[i];
                if (col.type == "date") {
                    for (var j = 0; j < httpResponse.data.content.length; j++) {
                        var row = httpResponse.data.content[j];
                        if (row[col.name] !== null && !(row[col.name] instanceof Date)) {
                            row[col.name] = new Date(row[col.name]);
                        }
                    }
                }
            }

            $scope.data = httpResponse.data.content;
            $scope.dataOptions.totalItems = httpResponse.data.count;
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

        DialogsService.confirmDialog(
            Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_DELETE,
            Trans.DATA_TABLE_STRUCTURE_DIALOG_MESSAGE_CONFIRM_DELETE,
            function (response) {
                $http.post(Paths.DATA_TABLE_COLUMNS_DELETE.pf($scope.object.id, names), {
                    objectTimestamp: $scope.object.updatedOn
                }).then(function (httpResponse) {
                    switch (httpResponse.data.result) {
                        case BaseController.RESULT_OK: {
                            $scope.setWorkingCopyObject();
                            $scope.fetchAllCollections();
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED: {
                            DialogsService.alertDialog(
                                Trans.DATA_TABLE_STRUCTURE_DIALOG_TITLE_DELETE,
                                httpResponse.data.errors.join("<br/>"),
                                "danger"
                            );
                            break;
                        }
                    }
                });
            }
        );
    };

    $scope.fetchColumn = function (id, column_name, callback) {
        $http.get($scope.fetchColumnObjectPath.pf(id, column_name)).then(function (httpResponse) {
            if (httpResponse.data !== null) {
                $scope.column = httpResponse.data;
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
            $scope.fetchAllCollections();
        }, function () {
        });
    };
    $scope.importCsv = function () {
        let modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "import_csv_dialog.html",
            controller: DataTableImportCsvController,
            size: "lg",
            resolve: {
                object: function () {
                    return $scope.object;
                },
                editable: function () {
                    return $scope.isEditable();
                }
            }
        });
        modalInstance.result.then(function (response) {
            $scope.setWorkingCopyObject();
            $scope.fetchAllCollections();

            //below will be called twice if structure changed too - fix it
            $scope.fetchDataCollection($scope.object.id);
        }, function (dirty) {
            if (dirty === true) {
                $scope.setWorkingCopyObject();
                $scope.fetchAllCollections();
            }
        });
    };

    $scope.onObjectChanged = function () {
        $scope.super.onObjectChanged();
        if ($scope.structureGridApi)
            $scope.structureGridApi.selection.clearSelectedRows();
        $scope.dataFilterOptions.filters = {};
        $scope.dataFilterOptions.sorting = [];
        $scope.dataFilterOptions.paging.page = 1;
    };

    $scope.onAfterPersist = function () {
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

concertoPanel.controller('DataTableController', ["$scope", "$uibModal", "$http", "$filter", "$timeout", "$state", "$sce", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "AdministrationSettingsService", "AuthService", "ScheduledTasksCollectionService", DataTableController]);
