/**
 * Select
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner3Controller($scope, uiGridConstants, AdministrationSettingsService, GridService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.gridService = GridService;
  $scope.options = [];
  $scope.selectOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    importerShowMenu: false,
    data: 'options',
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.selectOptions.enableFiltering = !$scope.selectOptions.enableFiltering;
          $scope.selectGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    columnDefs: [
      {
        displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_VALUE,
        field: "value"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_LABEL,
        field: "label"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_SELECT_LIST_FIELD_ORDER,
        type: "number",
        field: "order"
      }, {
        displayName: "",
        name: "_action",
        enableSorting: false,
        enableFiltering: false,
        enableCellEdit: false,
        exporterSuppressExport: true,
        cellTemplate:
        "<div class='ui-grid-cell-contents' align='center'>" +
        '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeOption(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_SELECT_LIST_BUTTON_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.selectGridApi = gridApi;
    },
    importerDataAddCallback: function (gridApi, newObjects) {
      $scope.param.definition.options = $scope.param.definition.options.concat(newObjects);
    },
    enableCellEditOnFocus: true
  };

  $scope.addOption = function () {
    if (!("options" in $scope.param.definition))
      $scope.param.definition.options = [];
    $scope.param.definition.options.push({
      value: "",
      label: ""
    });
  };

  $scope.deleteSelectedOptions = function () {
    var selectedRows = $scope.selectGridApi.selection.getSelectedRows();
    var rows = $scope.selectGridApi.grid.rows;
    for (var i = 0; i < selectedRows.length; i++) {
      for (var j = 0; j < rows.length; j++) {
        if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
          $scope.removeOption(j);
          break;
        }
      }
    }
  };

  $scope.deleteAllOptions = function () {
    $scope.param.definition.options = [];
  };

  $scope.removeOption = function (index) {
    $scope.param.definition.options.splice(index, 1);
  };

  $scope.$watch('param.definition.options', function (newValue) {
    $scope.options = newValue;
  });
};

concertoPanel.controller('WizardParamDefiner3Controller', ["$scope", "uiGridConstants", "AdministrationSettingsService", "GridService", WizardParamDefiner3Controller]);