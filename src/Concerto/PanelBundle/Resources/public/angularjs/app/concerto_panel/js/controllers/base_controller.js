function BaseController($scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, BaseCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService) {
    $scope.super = {};
    $scope.exportable = false;

    $scope.deletePath = "";
    $scope.addFormPath = "";
    $scope.fetchObjectPath = "";
    $scope.savePath = "";
    $scope.importPath = "";
    $scope.preImportStatusPath = "";
    $scope.saveNewPath = "";
    $scope.exportPath = "";
    $scope.exportInstructionsPath = "";
    $scope.lockPath = "";

    $scope.reloadOnModification = false;
    $scope.columnDefs = [];
    $scope.additionalColumnsDef = [];
    $scope.additionalListButtons = [];
    $scope.tabSection = "list";
    $scope.formTitle = "";
    $scope.formTitleAddLabel = "";
    $scope.formTitleEditLabel = "";
    $scope.collectionFilter = {
        starterContent: false
    };

    $scope.filterTimeout = false;
    $scope.collectionService = BaseCollectionService;
    $scope.dataTableCollectionService = DataTableCollectionService;
    $scope.testCollectionService = TestCollectionService;
    $scope.testWizardCollectionService = TestWizardCollectionService;
    $scope.userCollectionService = UserCollectionService;
    $scope.viewTemplateCollectionService = ViewTemplateCollectionService;
    $scope.gridService = GridService;
    $scope.dialogsService = DialogsService;
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.object = {
        id: 0,
        validationErrors: []
    };
    $scope.workingCopyObject = null;

    $scope.tabAccordion = {
        "form": {
            "open": true,
            "disabled": false
        }
    };

    $scope.isEditable = function (entity) {
        if (typeof (entity) === 'undefined') {
            entity = $scope.object;
        }
        if (entity.starterContent && !$scope.administrationSettingsService.starterContentEditable) return false;
        if ($scope.isLockedForMe(entity)) return false;
        return true;
    };

    $scope.isLockedForMe = function (entity) {
        if (typeof (entity) === 'undefined') {
            entity = $scope.object;
        }
        return $scope.isLocked(entity) && (AuthService.user === null || entity.lockedBy && entity.lockedBy != AuthService.user.id);
    };

    $scope.isLocked = function (entity) {
        if (typeof (entity) === 'undefined') {
            entity = $scope.object;
        }
        return entity.lockedBy !== null;
    };

    $scope.toggleLock = function () {
        $http.post($scope.lockPath.pf($scope.object.id), {
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
                        Trans.DIALOG_TITLE_LOCK,
                        httpResponse.data.errors.join("<br/>"),
                        "danger"
                    );
                }
            }
        });
    };

    $scope.setWorkingCopyObject = function () {
        $scope.workingCopyObject = {
            id: $scope.object.id,
            name: $scope.object.name,
            archived: $scope.object.archived,
            accessibility: $scope.object.accessibility,
            owner: $scope.object.owner,
            groups: $scope.object.groups
        };
    };

    $scope.updateFromWorkingCopy = function () {
        if ($scope.workingCopyObject == null || $scope.workingCopyObject.id != $scope.object.id)
            return;

        for (key in $scope.workingCopyObject) {
            $scope.object[key] = $scope.workingCopyObject[key];
        }
        $scope.workingCopyObject = null;
    };

    $scope.super.onObjectChanged = function () {
    };
    $scope.onObjectChanged = function () {
        $scope.super.onObjectChanged();
    };

    $scope.super.onCollectionChanged = function (newCollection) {
        if ($scope.collectionService) {
            $scope.collectionData = newCollection;
            $scope.updateFromWorkingCopy();
        }
    };
    $scope.onCollectionChanged = function (newCollection, oldCollection) {
        $scope.super.onCollectionChanged(newCollection, oldCollection);
    };

    $scope.fetchObjectCollection = function () {
        if ($scope.collectionGridApi)
            $scope.collectionGridApi.selection.clearSelectedRows();
        $scope.collectionService.fetchObjectCollection($scope.filterOptions);
    };

    $scope.refreshGrid = function () {
        $scope.collectionGridApi.core.refresh();
    };

    $scope.fetchObject = function (id) {
        var obj = $scope.collectionService.get(id);
        if (!obj)
            return null;
        $scope.object = obj;
        return $scope.object;
    };

    $scope.getGridLockedIconTooltip = function (object) {
        return Trans.LIST_LOCKED_TOOLTIP.pf(UserCollectionService.get(object.lockedBy).username);
    };

    $scope.initializeColumnDefs = function () {
        if ($scope.exportable) {
            $scope.columnDefs.push({
                displayName: Trans.LIST_FIELD_INFO,
                field: "description",
                enableSorting: false,
                exporterSuppressExport: true,
                cellTemplate: '<div class="ui-grid-cell-contents" align="center">' +
                    '<i style="vertical-align:middle;" class="glyphicon glyphicon-question-sign" uib-tooltip-html="COL_FIELD" tooltip-append-to-body="true"></i>' +
                    '<i style="vertical-align:middle;" class="glyphicon glyphicon-lock" ng-if="grid.appScope.isLocked(row.entity)" uib-tooltip-html="grid.appScope.getGridLockedIconTooltip(row.entity)" tooltip-append-to-body="true"></i>' +
                    '</div>',
                width: 70
            });
        }
        $scope.columnDefs.push({
            displayName: Trans.LIST_FIELD_ID,
            field: "id",
            type: "number",
            width: 75
        });
        for (var i = 0; i < $scope.additionalColumnsDef.length; i++) {
            $scope.columnDefs.push($scope.additionalColumnsDef[i]);
        }
        $scope.columnDefs.push({
            displayName: Trans.LIST_FIELD_UPDATED_BY,
            field: "updatedBy",
        });
        $scope.columnDefs.push({
            displayName: Trans.LIST_FIELD_UPDATED_ON,
            field: "updatedOn",
            cellTemplate: '<div class="ui-grid-cell-contents">{{COL_FIELD * 1000|date:\'yyyy-MM-dd HH:mm:ss\'}}</div>',
        });
        var cellTemplate = '<div class="ui-grid-cell-contents" align="center">';
        for (var i = 0; i < $scope.additionalListButtons.length; i++) {
            cellTemplate += $scope.additionalListButtons[i];
        }
        cellTemplate += '<button class="btn btn-default btn-xs" ng-click="grid.appScope.edit(row.entity.id);">' + Trans.LIST_EDIT + '</button>';
        if ($scope.exportable) {
            cellTemplate += '<button class="btn btn-default btn-xs" ng-click="grid.appScope.export(row.entity.id);">' + Trans.LIST_EXPORT + '</button>';
        }
        cellTemplate += '<button ng-disabled="!grid.appScope.isEditable(row.entity)" class="btn btn-danger btn-xs" ng-click="grid.appScope.delete(row.entity.id);">' + Trans.LIST_DELETE + '</button>' +
            '</div>';
        $scope.columnDefs.push({
            displayName: "",
            name: "_action",
            enableSorting: false,
            enableFiltering: false,
            exporterSuppressExport: true,
            cellTemplate: cellTemplate
        });
    };

    $scope.accessibilities = [
        {value: 2, label: Trans.ACCESSIBILITY_PUBLIC},
        {value: 1, label: Trans.ACCESSIBILITY_GROUP},
        {value: 0, label: Trans.ACCESSIBILITY_PRIVATE}
    ];

    $scope.filterOptions = {
        filters: {},
        sorting: [],
        paging: {
            page: 1,
            pageSize: 500
        }
    };

    $scope.collectionData = [];
    $scope.collectionOptions = {
        rowTemplate:
            '<div ng-class="{ \'grid-row-locked-by-other\': grid.appScope.isLockedForMe(row.entity) }">' +
            '<div ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
            '</div>',
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "collectionData",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        columnDefs: $scope.columnDefs,
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
        gridMenuCustomItems: [
            {
                title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
                action: function ($event) {
                    $scope.collectionOptions.enableFiltering = !$scope.collectionOptions.enableFiltering;
                    $scope.collectionGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
                }
            }
        ],
        onRegisterApi: function (gridApi) {
            gridApi.grid.registerRowsProcessor($scope.filterByStarterContent, 200);
            gridApi.core.on.filterChanged($scope, function () {
                for (let i = 0; i < gridApi.grid.columns.length; i++) {
                    let filters = $scope.gridFilters[$scope.tabStateName];
                    filters[i] = gridApi.grid.columns[i].filters[0].term;
                }
            });
            $scope.collectionGridApi = gridApi;

            $timeout(function () {
                let filters = $scope.gridFilters[$scope.tabStateName];
                if (typeof filters !== 'undefined') {
                    for (let i = 0; i < gridApi.grid.columns.length; i++) {
                        gridApi.grid.columns[i].filters[0].term = filters[i];
                    }
                }
            });
        }
    };

    $scope.filterByStarterContent = function (renderableRows) {
        renderableRows.forEach(function (row) {
            if ($scope.collectionFilter.starterContent != row.entity.starterContent)
                row.visible = false;
        });
        return renderableRows;
    };

    $scope.deleteSelected = function () {
        var ids = [];
        for (var i = 0; i < $scope.collectionGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.collectionGridApi.selection.getSelectedRows()[i].id);
        }
        $scope.delete(ids);
    };

    $scope.deleteObject = function () {
        var ids = [$scope.object.id];
        $scope.delete(ids);
    };

    $scope.delete = function (ids) {
        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        DialogsService.confirmDialog(
            Trans.DIALOG_TITLE_DELETE,
            Trans.DIALOG_MESSAGE_CONFIRM_DELETE,
            function (response) {
                if ($scope.object != null && ids.indexOf($scope.object.id) !== -1 && !$scope.reloadOnModification) {
                    $scope.cancel();
                }

                $http.post($scope.deletePath.pf(ids.join(",")), {
                    objectTimestamp: $scope.object.updatedOn
                }).then(function (httpResponse) {
                    switch (httpResponse.data.result) {
                        case BaseController.RESULT_OK: {
                            if ($scope.reloadOnModification)
                                location.reload();
                            else {
                                $scope.fetchAllCollections();
                                if ($scope.onDelete)
                                    $scope.onDelete();
                            }
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED: {
                            DialogsService.alertDialog(
                                Trans.DIALOG_TITLE_DELETE,
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

    $scope.resetObject = function () {
        $scope.object = {id: 0, accessibility: 0};
    };

    $scope.add = function () {
        $scope.resetObject();
        $scope.tabAccordion.form.open = true;
        $scope.tabAccordion.form.disabled = true;
        var modalInstance = $uibModal.open({
            templateUrl: $scope.addFormPath,
            controller: SaveController,
            scope: $scope,
            size: "prc-lg"
        });

        modalInstance.result.then(function (object) {
            $scope.object = object;
            $scope.tabAccordion.form.disabled = false;
        }, function () {
            $scope.tabAccordion.form.disabled = false;
        });
    };

    $scope.getPersistObject = function () {
        let obj = angular.copy($scope.object);
        obj.objectTimestamp = $scope.object.updatedOn;
        return obj;
    };

    $scope.persist = function (modalInstance) {
        $scope.object.validationErrors = [];

        var oid = $scope.object.id;

        if ($scope.onBeforePersist)
            $scope.onBeforePersist();

        var addModalDialog = modalInstance;
        $http.post($scope.savePath.pf(oid), $scope.getPersistObject()).then(
            function successCallback(httpResponse) {
                switch (httpResponse.data.result) {
                    case BaseController.RESULT_OK: {
                        if (addModalDialog != null) {
                            addModalDialog.close($scope.object);
                        }
                        $scope.fetchAllCollections();

                        if ($scope.onAfterPersist) {
                            $scope.onAfterPersist();
                        }
                        if ($scope.reloadOnModification) {
                            location.reload();
                        } else {
                            $scope.edit(httpResponse.data.object.id);
                        }
                        break;
                    }
                    default: {
                        $scope.object.validationErrors = httpResponse.data.errors;
                        $timeout(() => {
                            let alert = $(".alert-danger").first();
                            if(alert.length > 0) alert[0].scrollIntoView({behavior: "smooth"});
                        });
                        break;
                    }
                }
            },
            function errorCallback(response) {
                $scope.dialogsService.alertDialog(
                    Trans.DIALOG_TITLE_SAVE,
                    Trans.DIALOG_MESSAGE_FAILED,
                    "danger"
                );
            });
    };

    $scope.edit = function (id) {
        $state.go($scope.tabStateName + "Form", {'id': id});
    };

    $scope.import = function () {

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "import_dialog.html",
            controller: ImportController,
            scope: $scope,
            size: "prc-lg",
            resolve: {
                importPath: function () {
                    return $scope.importPath;
                },
                preImportStatusPath: function () {
                    return $scope.preImportStatusPath;
                }
            }
        });

        modalInstance.result.then(function () {
            ScheduledTasksCollectionService.fetchObjectCollection(function () {
                if (ScheduledTasksCollectionService.ongoingScheduledTasks.length > 0) {
                    ScheduledTasksCollectionService.launchOngoingTaskDialog();
                }
            });
        }, function (dirty) {
            if (dirty === true) {
                $scope.fetchAllCollections();
            }
        });
    };

    $scope.fetchAllCollections = function () {
        if ($scope.collectionGridApi)
            $scope.collectionGridApi.selection.clearSelectedRows();

        $scope.dataTableCollectionService.fetchObjectCollection();
        $scope.testCollectionService.fetchObjectCollection();
        $scope.testWizardCollectionService.fetchObjectCollection();
        $scope.viewTemplateCollectionService.fetchObjectCollection();
        $scope.userCollectionService.fetchObjectCollection();
    };

    $scope.saveNew = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "save_new_dialog.html",
            controller: SaveNewController,
            scope: $scope,
            size: "lg",
            resolve: {
                name: function () {
                    return $scope.object.name;
                },
                saveNewPath: function () {
                    return $scope.saveNewPath;
                }
            }
        });

        modalInstance.result.then(function (object) {
            $scope.object = object;
            $scope.fetchAllCollections();
        }, function () {
        });
    };

    $scope.exportSelected = function () {
        var ids = [];
        for (var i = 0; i < $scope.collectionGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.collectionGridApi.selection.getSelectedRows()[i].id);
        }

        if (ids.length == 0) {
            $scope.dialogsService.alertDialog(
                Trans.EXPORT_DIALOG_TITLE,
                Trans.EXPORT_DIALOG_EMPTY_LIST_ERROR_CONTENT,
                "warning"
            );
        } else {
            $scope.export(ids);
        }
    };

    $scope.exportObject = function () {
        var ids = [$scope.object.id];
        $scope.export(ids);
    };

    $scope.export = function (ids) {

        if (!(ids instanceof Array)) {
            ids = [ids];
        }

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'export_dialog.html',
            controller: ExportController,
            size: "lg",
            resolve: {
                ids: function () {
                    return ids;
                },
                exportInstructionsPath: function () {
                    return $scope.exportInstructionsPath;
                }
            }
        });
        modalInstance.result.then(function (response) {
            window.open($scope.exportPath.pf(angular.toJson(response.instructions), response.format), "_blank");
        });
    };

    $scope.editDescription = function () {
        $scope.dialogsService.ckeditorDialog(
            Trans.DESCRIPTION_DIALOG_TITLE,
            Trans.DESCRIPTION_DIALOG_TOOLTIP,
            $scope.object.description,
            function (newVal) {
                $scope.object.description = newVal;
                $scope.persist();
            }
        );
    };

    $scope.switchTab = function (id) {
        if (id == null) {
            $state.go($scope.tabStateName, {}, {location: 'replace'});
        } else
            $state.go($scope.tabStateName + "Form", {id: id}, {location: 'replace'});
    };

    $scope.cancel = function () {
        $scope.resetObject();
        $scope.switchTab();
    };

    $scope.$watch("object.id", function (newObject, oldObject) {
        if (newObject == null)
            return;
        if (oldObject == null || newObject !== oldObject) {
            if (newObject > 0) {
                $scope.formTitle = $scope.formTitleEditLabel.pf(newObject);
            } else {
                $scope.formTitle = $scope.formTitleAddLabel;
                if ($scope.tabSection === "form")
                    $scope.tabSection = "list";
            }
        }
        $scope.onObjectChanged();
    });

    $scope.$watch("collectionService.collection", function (newCollection, oldCollection) {
        var id = null;
        if ($scope.object != null && $scope.object.id != 0)
            id = $scope.object.id;
        if (id != null) {
            $scope.object = $scope.collectionService.get($scope.object.id);
        }
        $scope.onCollectionChanged(newCollection, oldCollection);
    });

    $scope.$on('$locationChangeStart', function (event, toUrl, fromUrl) {
        //required to disable maximization styles of CKEditor
        for (var name in CKEDITOR.instances) {
            if (CKEDITOR.instances[name] !== undefined && CKEDITOR.instances[name].getCommand("maximize") !== undefined)
                if (CKEDITOR.instances[name].getCommand("maximize").state == 1) {
                    CKEDITOR.instances[name].execCommand("maximize");
                }
        }
    });

    $scope.isDelayedEditPossible = function () {
        return $scope.collectionService.collectionInitialized;
    };

    $scope.delayedEdit = function (id) {
        if (!$scope.isDelayedEditPossible()) {
            $timeout(function () {
                $scope.delayedEdit(id);
            }, 100);
            return;
        }
        var obj = $scope.fetchObject(id);
        if (!obj) {
            $scope.switchTab();
        }
    };

    $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
        $scope.fetchAllCollections();
        if (toState.name === $scope.tabStateName + "Form") {
            $scope.tabSection = "form";
            $scope.delayedEdit(toParams.id);
        } else if (toState.name === $scope.tabStateName) {
            $scope.tabSection = "list";
            $scope.resetObject();
        }
    });

    if ($state.current.name === $scope.tabStateName + "Form") {
        $scope.tabSection = "form";
        $scope.delayedEdit($state.params.id);
    }
}

BaseController.RESULT_OK = 0;
BaseController.RESULT_VALIDATION_FAILED = 1;
BaseController.RESULT_OPERATION_NOT_SUPPORTED = 2;

