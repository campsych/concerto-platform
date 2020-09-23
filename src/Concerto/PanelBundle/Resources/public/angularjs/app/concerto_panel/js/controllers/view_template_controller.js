function ViewTemplateController($scope, $uibModal, $http, $filter, $state, $sce, $timeout, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService) {
    $scope.tabStateName = "templates";
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, ViewTemplateCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService, AuthService, ScheduledTasksCollectionService);
    $scope.exportable = true;

    $scope.deletePath = Paths.VIEW_TEMPLATE_DELETE;
    $scope.addFormPath = Paths.VIEW_TEMPLATE_ADD_FORM;
    $scope.fetchObjectPath = Paths.VIEW_TEMPLATE_FETCH_OBJECT;
    $scope.savePath = Paths.VIEW_TEMPLATE_SAVE;
    $scope.importPath = Paths.VIEW_TEMPLATE_IMPORT;
    $scope.preImportStatusPath = Paths.VIEW_TEMPLATE_PRE_IMPORT_STATUS;
    $scope.exportPath = Paths.VIEW_TEMPLATE_EXPORT;
    $scope.saveNewPath = Paths.VIEW_TEMPLATE_SAVE_NEW;
    $scope.exportInstructionsPath = Paths.VIEW_TEMPLATE_EXPORT_INSTRUCTIONS;
    $scope.lockPath = Paths.VIEW_TEMPLATE_LOCK;

    $scope.formTitleAddLabel = Trans.VIEW_TEMPLATE_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.VIEW_TEMPLATE_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;

    $scope.additionalColumnsDef = [{
        displayName: Trans.VIEW_TEMPLATE_LIST_FIELD_NAME,
        field: "name"
    }];

    // A hack to delay codemirror refresh, this variable should be changed shortly after changing scope contents
    // to make sure that codemirror properly refreshes its view
    $scope.codemirrorForceRefresh = 1;
    $scope.$watchCollection(
        "[ tabAccordion.source.open, object.id, tabSection ]",
        function () {
            $timeout(function () {
                $scope.codemirrorForceRefresh++;
            }, 20);
        }
    );

    $scope.tabAccordion.source = {
        open: true
    };

    $scope.headCodeOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'htmlmixed',
        viewportMargin: Infinity,
        readOnly: !$scope.isEditable(),
        extraKeys: {
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen"))
                    cm.setOption("fullScreen", false);
            }
        }
    };

    $scope.cssCodeOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'css',
        viewportMargin: Infinity,
        readOnly: !$scope.isEditable(),
        extraKeys: {
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen"))
                    cm.setOption("fullScreen", false);
            }
        }
    };

    $scope.jsCodeOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'javascript',
        viewportMargin: Infinity,
        readOnly: !$scope.isEditable(),
        extraKeys: {
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen"))
                    cm.setOption("fullScreen", false);
            }
        }
    };

    $scope.htmlEditorOptions = Defaults.ckeditorTestContentOptions;

    $scope.resetObject = function () {
        $scope.object = {
            id: 0,
            accessibility: 0,
            name: "",
            description: "",
            head: "",
            html: "",
            css: "",
            js: ""
        };
    };

    $scope.updateCKEditorCSS = function () {
        var styleId = 'ckeditor-concerto-template';
        if (CKEDITOR.instances.templateHtml && CKEDITOR.instances.templateHtml.document && CKEDITOR.instances.templateHtml.document.$) {
            var doc = CKEDITOR.instances.templateHtml.document.$;
            var style = doc.getElementById(styleId);
            if (!style) {
                var head = doc.getElementsByTagName('head')[0];
                style = doc.createElement('style');
                style.id = styleId;
                head.appendChild(style);
            }
            $(style).html($scope.object.css);
        }
    };

    $scope.onObjectChanged = function () {
        $scope.headCodeOptions.readOnly = !$scope.isEditable();
        $scope.cssCodeOptions.readOnly = !$scope.isEditable();
        $scope.jsCodeOptions.readOnly = !$scope.isEditable();
    };

    $scope.onAfterPersist = function () {
    };

    $scope.setWorkingCopyObject = function () {
        $scope.workingCopyObject = {
            id: $scope.object.id,
            name: $scope.object.name,
            archived: $scope.object.archived,
            accessibility: $scope.object.accessibility,
            owner: $scope.object.owner,
            groups: $scope.object.groups,
            head: $scope.object.head,
            css: $scope.object.css,
            js: $scope.object.js,
            html: $scope.object.html
        };
    };

    $scope.$watch("object.css", function () {
        if (CKEDITOR.instances.templateHtml && CKEDITOR.instances.templateHtml.document && CKEDITOR.instances.templateHtml.document.$) {
            $scope.updateCKEditorCSS();
        }
    });

    CKEDITOR.on("instanceReady", function (event) {
        if (event.editor.name == "templateHtml") {
            event.editor.on("change", function (event) {
                $scope.updateCKEditorCSS();
            });
            $scope.updateCKEditorCSS();
        }
    });

    $scope.resetObject();
    $scope.initializeColumnDefs();
}

ViewTemplateController.prototype = Object.create(BaseController.prototype);
concertoPanel.controller('ViewTemplateController', ["$scope", "$uibModal", "$http", "$filter", "$state", "$sce", "$timeout", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "AdministrationSettingsService", "AuthService", "ScheduledTasksCollectionService", ViewTemplateController]);