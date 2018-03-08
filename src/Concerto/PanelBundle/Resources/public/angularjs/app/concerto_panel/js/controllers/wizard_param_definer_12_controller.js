/**
 * Table Map
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner12Controller($scope, uiGridConstants, GridService) {
  $scope.gridService = GridService;
  $scope.colMap = [];
  $scope.colMapOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    importerShowMenu: false,
    data: 'colMap',
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.colMapOptions.enableFiltering = !$scope.colMapOptions.enableFiltering;
          $scope.colMapGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    columnDefs: [
      {
        displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_NAME,
        field: "name"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_LABEL,
        field: "label"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_COLUMN_MAP_FIELD_TOOLTIP,
        field: "tooltip"
      }, {
        displayName: "",
        name: "_action",
        enableSorting: false,
        enableFiltering: false,
        enableCellEdit: false,
        exporterSuppressExport: true,
        cellTemplate:
        "<div class='ui-grid-cell-contents' align='center'>" +
        '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeColumn(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_COLUMN_MAP_LIST_BUTTON_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.colMapGridApi = gridApi;
    },
    importerDataAddCallback: function (gridApi, newObjects) {
      $scope.param.definition.cols = $scope.param.definition.cols.concat(newObjects);
    },
    enableCellEditOnFocus: true
  };

  $scope.addColumn = function () {
    if (!("cols" in $scope.param.definition))
      $scope.param.definition.cols = [];
    $scope.param.definition.cols.push({
      name: "",
      label: "",
      tooltip: ""
    });
  };

  $scope.deleteSelectedColumns = function () {
    var selectedRows = $scope.selectGridApi.selection.getSelectedRows();
    var rows = $scope.colMapGridApi.grid.rows;
    for (var i = 0; i < selectedRows.length; i++) {
      for (var j = 0; j < rows.length; j++) {
        if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
          $scope.removeColumn(j);
          break;
        }
      }
    }
  };

  $scope.deleteAllColumns = function () {
    $scope.param.definition.cols = [];
  };

  $scope.removeColumn = function (index) {
    $scope.param.definition.cols.splice(index, 1);
  };

  $scope.$watch('param.definition.cols', function (newValue) {
    $scope.colMap = newValue;
  });
};

concertoPanel.controller('WizardParamDefiner12Controller', ["$scope", "uiGridConstants", "GridService", WizardParamDefiner12Controller]);