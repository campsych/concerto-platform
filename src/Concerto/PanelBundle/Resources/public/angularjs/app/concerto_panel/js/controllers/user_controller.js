function UserController($scope, $uibModal, $http, $filter, $state, $sce, $timeout, uiGridConstants, GridService, DialogsService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService) {
    $scope.tabStateName = "users";
    BaseController.call(this, $scope, $uibModal, $http, $filter, $state, $timeout, uiGridConstants, GridService, DialogsService, UserCollectionService, DataTableCollectionService, TestCollectionService, TestWizardCollectionService, UserCollectionService, ViewTemplateCollectionService, AdministrationSettingsService);
    $scope.exportable = false;
    $scope.reloadOnModification = true;

    $scope.deletePath = Paths.USER_DELETE;
    $scope.addFormPath = Paths.USER_ADD_FORM;
    $scope.fetchObjectPath = Paths.USER_FETCH_OBJECT;
    $scope.savePath = Paths.USER_SAVE;

    $scope.formTitleAddLabel = Trans.USER_FORM_TITLE_ADD;
    $scope.formTitleEditLabel = Trans.USER_FORM_TITLE_EDIT;
    $scope.formTitle = $scope.formTitleAddLabel;
    $scope.additionalColumnsDef = [
        {
            displayName: Trans.USER_LIST_FIELD_USERNAME,
            field: "username"
        }, {
            displayName: Trans.USER_LIST_FIELD_EMAIL,
            field: "email"
        }];

    $scope.resetObject = function () {
        $scope.object = {
            id: 0,
            accessibility: 0,
            username: "",
            email: ""
        };
    };

    $scope.logIn = function () {
        $("#formLogin").submit();
    };

    $scope.resetObject();
    $scope.initializeColumnDefs();
}

concertoPanel.controller('UserController', ["$scope", "$uibModal", "$http", "$filter", "$state", "$sce", "$timeout", "uiGridConstants", "GridService", "DialogsService", "DataTableCollectionService", "TestCollectionService", "TestWizardCollectionService", "UserCollectionService", "ViewTemplateCollectionService", "AdministrationSettingsService", UserController]);