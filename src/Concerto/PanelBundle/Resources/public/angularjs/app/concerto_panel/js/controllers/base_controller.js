function BaseController($scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, BaseCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService) {
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

    $scope.reloadOnModification = false;
    $scope.columnDefs = [];
    $scope.additionalColumnsDef = [];
    $scope.additionalListButtons = [];
    $scope.tabSection = "list";
    $scope.formTitle = "";
    $scope.formTitleAddLabel = "";
    $scope.formTitleEditLabel = "";

    $scope.filterTimeout = false;
    $scope.collectionService = BaseCollectionService;
    $scope.dataTableCollectionService = DataTableCollectionService;
    $scope.testCollectionService = TestCollectionService;
    $scope.testWizardCollectionService = TestWizardCollectionService;
    $scope.userCollectionService = UserCollectionService;
    $scope.viewTemplateCollectionService = ViewTemplateCollectionService;
    $scope.gridService = GridService;

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

    $scope.setWorkingCopyObject = function () {
        $scope.workingCopyObject = {
            id: $scope.object.id,
            name: $scope.object.name,
            protected: $scope.object.protected,
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
    }

    $scope.super.onObjectChanged = function (newObject, oldObject) {
    };
    $scope.onObjectChanged = function (newObject, oldObject) {
        $scope.super.onObjectChanged(newObject, oldObject);
    };

    $scope.super.onCollectionChanged = function (newCollection) {
        if ($scope.collectionService) {
            $scope.collectionOptions.enableFiltering = newCollection.length > 0;
            if ($scope.collectionGridApi && uiGridConstants.dataChange) {
                $scope.collectionGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
            }

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

    $scope.refresh = function () {
        $scope.fetchObjectCollection();
    };

    $scope.fetchObject = function (id) {
        var obj = $scope.collectionService.get(id);
        if (!obj)
            return null;
        $scope.object = obj;
        if (!("initProtected" in $scope.object))
            $scope.object.initProtected = $scope.object.protected;
        return $scope.object;
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
                        '</div>',
                width: 50
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
            field: "updatedByName",
        });
        $scope.columnDefs.push({
            displayName: Trans.LIST_FIELD_UPDATED_ON,
            field: "updatedOn",
        });
        $scope.columnDefs.push({
            displayName: Trans.LIST_FIELD_PROTECTED,
            field: "protected",
            filter: {
                term: '0',
                type: uiGridConstants.filter.SELECT,
                selectOptions: [{value: '0', label: Trans.TEST_VARS_PARAMS_LIST_FIELD_URL_NO}, {value: '1', label: Trans.TEST_VARS_PARAMS_LIST_FIELD_URL_YES}]
            },
            cellTemplate: '<div class="ui-grid-cell-contents" align="center">{{COL_FIELD | logical}}</div>'
        });
        var cellTemplate = '<div class="ui-grid-cell-contents" align="center">';
        for (var i = 0; i < $scope.additionalListButtons.length; i++) {
            cellTemplate += $scope.additionalListButtons[i];
        }
        cellTemplate += '<button class="btn btn-default btn-xs" ng-click="grid.appScope.edit(row.entity.id);">' + Trans.LIST_EDIT + '</button>';
        if ($scope.exportable) {
            cellTemplate += '<button class="btn btn-default btn-xs" ng-click="grid.appScope.export(row.entity.id);">' + Trans.LIST_EXPORT + '</button>';
        }
        cellTemplate += '<button ng-disabled="row.entity.initProtected == \'1\'" class="btn btn-danger btn-xs" ng-click="grid.appScope.delete(row.entity.id);">' + Trans.LIST_DELETE + '</button>' +
                '</div>';
        $scope.columnDefs.push({
            displayName: "",
            name: "_action",
            enableSorting: false,
            enableFiltering: false,
            exporterSuppressExport: true,
            cellTemplate: cellTemplate,
            width: 200
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
        enableFiltering: true,
        enableGridMenu: true,
        exporterMenuCsv: false,
        exporterMenuPdf: false,
        data: "collectionData",
        exporterCsvFilename: 'export.csv',
        showGridFooter: true,
        columnDefs: $scope.columnDefs,
        onRegisterApi: function (gridApi) {
            $scope.collectionGridApi = gridApi;
        }
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

        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
            controller: ConfirmController,
            size: "sm",
            resolve: {
                title: function () {
                    return Trans.DIALOG_TITLE_DELETE;
                },
                content: function () {
                    return Trans.DIALOG_MESSAGE_CONFIRM_DELETE;
                }
            }
        });

        modalInstance.result.then(function (response) {

            if ($scope.object != null && ids.indexOf($scope.object.id) !== -1 && !$scope.reloadOnModification) {
                $scope.cancel();
            }

            $http.post($scope.deletePath.pf(ids.join(",")), {
            }).success(function (data) {
                switch (data.result) {
                    case BaseController.RESULT_OK:
                    {
                        if ($scope.reloadOnModification)
                            location.reload();
                        else {
                            $scope.fetchObjectCollection();
                            if ($scope.onDelete)
                                $scope.onDelete();
                        }
                        break;
                    }
                    case BaseController.RESULT_VALIDATION_FAILED:
                    {
                        $uibModal.open({
                            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                            controller: AlertController,
                            size: "sm",
                            resolve: {
                                title: function () {
                                    return Trans.DIALOG_TITLE_DELETE;
                                },
                                content: function () {
                                    return data.errors.join("<br/>");
                                },
                                type: function () {
                                    return "danger";
                                }
                            }
                        });
                    }
                }
            });
        }, function () {
        });
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
        return $scope.object;
    };

    $scope.persist = function (modalInstance) {
        $scope.object.validationErrors = [];

        var oid = $scope.object.id;

        if ($scope.onBeforePersist)
            $scope.onBeforePersist();

        var addModalDialog = modalInstance;
        $http.post($scope.savePath.pf(oid), $scope.getPersistObject()).then(
                function successCallback(response) {
                    switch (response.data.result) {
                        case BaseController.RESULT_OK:
                        {
                            if (addModalDialog != null) {
                                addModalDialog.close($scope.object);
                            }
                            $scope.fetchObjectCollection();

                            var modalInstance = $uibModal.open({
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

                            modalInstance.result.then(function (r) {
                                if ($scope.onAfterPersist) {
                                    $scope.onAfterPersist();
                                }
                                if ($scope.reloadOnModification) {
                                    location.reload();
                                } else {
                                    $scope.edit(response.data.object_id);
                                    $scope.object.initProtected = $scope.object.protected;
                                }
                            });
                            break;
                        }
                        case BaseController.RESULT_VALIDATION_FAILED:
                        {
                            $scope.object.validationErrors = response.data.errors;
                            $(".modal").animate({scrollTop: 0}, "slow");
                            break;
                        }
                    }
                },
                function errorCallback(response) {
                    $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                        controller: AlertController,
                        size: "sm",
                        resolve: {
                            title: function () {
                                return Trans.DIALOG_TITLE_SAVE;
                            },
                            content: function () {
                                return Trans.DIALOG_MESSAGE_FAILED;
                            },
                            type: function () {
                                return "danger";
                            }
                        }
                    });
                });
    };

    $scope.edit = function (id) {
        $state.go($scope.tabStateName + "Form", {'id': id});
        $scope.fetchObject(id);
        $scope.tabSection = "form";
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

        modalInstance.result.then(function (object) {
            $scope.object = object;
            $scope.fetchAllCollections();
        }, function () {
        });
    };

    $scope.fetchAllCollections = function () {
        $scope.dataTableCollectionService.fetchObjectCollection();
        $scope.testCollectionService.fetchObjectCollection();
        $scope.testWizardCollectionService.fetchObjectCollection();
        $scope.viewTemplateCollectionService.fetchObjectCollection();
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
            $scope.fetchObjectCollection();
        }, function () {
        });
    };

    $scope.exportSelected = function () {
        var ids = [];
        for (var i = 0; i < $scope.collectionGridApi.selection.getSelectedRows().length; i++) {
            ids.push($scope.collectionGridApi.selection.getSelectedRows()[i].id);
        }

        if (ids.length == 0) {
            var modalInstance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'alert_dialog.html',
                controller: AlertController,
                size: "sm",
                resolve: {
                    title: function () {
                        return Trans.EXPORT_DIALOG_TITLE;
                    },
                    content: function () {
                        return Trans.EXPORT_DIALOG_EMPTY_LIST_ERROR_CONTENT;
                    },
                    type: function () {
                        return "warning";
                    }
                }
            });
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
                title: function () {
                    return Trans.EXPORT_DIALOG_TITLE;
                },
                content: function () {
                    return Trans.EXPORT_DIALOG_EMPTY_LIST_ERROR_CONTENT;
                },
                ids: function () {
                    return ids;
                }
            }
        });
        modalInstance.result.then(function (response) {
            window.open($scope.exportPath.pf(ids) + "/" + response, "_blank");
        });
    };

    $scope.editDescription = function () {
        var modalInstance = $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "ckeditor_dialog.html",
            controller: CKEditorController,
            resolve: {
                title: function () {
                    return Trans.DESCRIPTION_DIALOG_TITLE;
                },
                tooltip: function () {
                    return Trans.DESCRIPTION_DIALOG_TOOLTIP;
                },
                value: function () {
                    return $scope.object.description;
                }
            },
            size: "lg"
        });

        modalInstance.result.then(function (newVal) {
            $scope.object.description = newVal;
        });
    };

    $scope.showSingleTextareaModal = function (value, readonly, title, tooltip) {
        $uibModal.open({
            templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "textarea_dialog.html",
            controller: TextareaController,
            resolve: {
                readonly: function () {
                    return readonly;
                },
                value: function () {
                    return value;
                },
                title: function () {
                    return title;
                },
                tooltip: function () {
                    return tooltip;
                }
            },
            size: "lg"
        });
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
    }

    $scope.$watchCollection("object", function (newObject, oldObject) {
        if (newObject == null)
            return;
        if (oldObject == null || newObject.id !== oldObject.id) {
            if (newObject.id > 0) {
                $scope.formTitle = $scope.formTitleEditLabel.pf(newObject.id);
            } else {
                $scope.formTitle = $scope.formTitleAddLabel;
                if ($scope.tabSection === "form")
                    $scope.tabSection = "list";
            }
        }
        $scope.onObjectChanged(newObject, oldObject);
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
        if (CKEDITOR.instances.editor1 !== undefined && CKEDITOR.instances.editor1.getCommand("maximize") !== undefined)
            if (CKEDITOR.instances.editor1.getCommand("maximize").state == 1) {
                CKEDITOR.instances.editor1.execCommand("maximize");
            }
    });

    $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
        if (toState.name === $scope.tabStateName || toState.name === $scope.tabStateName + "Form") {
            if (toState.name === $scope.tabStateName + "Form") {
                $scope.tabSection = "form";
                $scope.delayedEdit(toParams.id);
            } else {
                $scope.tab.activeIndex = $scope.tabIndex;
                $scope.tabSection = "list";
                $scope.resetObject();
            }
        } else {
            $scope.resetObject();
        }
    });

    $scope.delayedEdit = function (id) {
        if (!$scope.collectionService.collectionInitialized) {
            $timeout(function () {
                $scope.delayedEdit(id);
            }, 100);
            return;
        }
        var obj = $scope.fetchObject(id);
        if (!obj) {
            $scope.switchTab();
        }
        $scope.tab.activeIndex = $scope.tabIndex;
    };
}

BaseController.RESULT_OK = 0;
BaseController.RESULT_VALIDATION_FAILED = 1;
BaseController.RESULT_OPERATION_NOT_SUPPORTED = 2;

