function ViewTemplateController($scope, $uibModal, $http, $filter, $state, $sce, $timeout, uiGridConstants, GridService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService) {
    $scope.tabStateName = "templates";
    $scope.tabIndex = 1;
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, ViewTemplateCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService);
    $scope.exportable = true;

    $scope.deletePath = Paths.VIEW_TEMPLATE_DELETE;
    $scope.addFormPath = Paths.VIEW_TEMPLATE_ADD_FORM;
    $scope.fetchObjectPath = Paths.VIEW_TEMPLATE_FETCH_OBJECT;
    $scope.savePath = Paths.VIEW_TEMPLATE_SAVE;
    $scope.importPath = Paths.VIEW_TEMPLATE_IMPORT;
    $scope.exportPath = Paths.VIEW_TEMPLATE_EXPORT;
    $scope.saveNewPath = Paths.VIEW_TEMPLATE_SAVE_NEW;

    $scope.formTitleAddLabel = Trans.VIEW_TEMPLATE_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.VIEW_TEMPLATE_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;
    
    $scope.additionalColumnsDef = [{
            displayName: Trans.VIEW_TEMPLATE_LIST_FIELD_NAME,
            field: "name"
        }];
    
    // A hack to delay codemirror refresh, this variable should be changed shortly after changing scope contents
    // to make sure that codemirror properly refreshes its view
    $scope.codemirrorForceRefresh= 1;
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
            html: ""
        };
    };

    $scope.resetObject();
    $scope.initializeColumnDefs();
    $scope.fetchObjectCollection();
}

ViewTemplateController.prototype = Object.create(BaseController.prototype);
concertoPanel.controller('ViewTemplateController', ["$scope", "$uibModal", "$http", "$filter", "$state", "$sce", "$timeout", "uiGridConstants", "GridService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", ViewTemplateController]);