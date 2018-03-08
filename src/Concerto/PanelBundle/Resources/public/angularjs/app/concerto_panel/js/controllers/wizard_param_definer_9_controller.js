/**
 * Group
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner9Controller($scope, $filter, uiGridConstants, GridService) {
  $scope.gridService = GridService;
  $scope.sortedTypesCollection = $filter('orderBy')($scope.typesCollection, "label");
  $scope.fields = [];
  $scope.groupOptions = {
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    data: "fields",
    exporterCsvFilename: 'export.csv',
    showGridFooter: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.groupOptions.enableFiltering = !$scope.groupOptions.enableFiltering;
          $scope.groupGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    columnDefs: [
      {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_NAME,
        field: "name"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_LABEL,
        field: "label"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_TYPE,
        field: "type",
        editableCellTemplate: 'ui-grid/dropdownEditor',
        editDropdownOptionsArray: $scope.sortedTypesCollection,
        editDropdownIdLabel: "id",
        editDropdownValueLabel: "label",
        cellTemplate:
        "<div class='ui-grid-cell-contents'>" +
        "{{grid.appScope.typesCollection[row.entity.type].label}}" +
        "</div>"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_HIDE_CONDITION,
        field: "hideCondition"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_DEFINITION,
        field: "definition",
        enableCellEdit: false,
        enableSorting: false,
        enableFiltering: false,
        exporterSuppressExport: true,
        cellTemplate:
        "<div class='ui-grid-cell-contents' bind-html-compile='grid.appScope.getParamDefinitionCellTemplate(row.entity)'>" +
        "</div>"
      }, {
        displayName: Trans.TEST_WIZARD_PARAM_GROUP_LIST_FIELD_ORDER,
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
        '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeField(grid.renderContainers.body.visibleRowCache.indexOf(row));">' + Trans.TEST_WIZARD_PARAM_GROUP_LIST_BUTTON_DELETE + '</button>' +
        "</div>",
        width: 100
      }
    ],
    onRegisterApi: function (gridApi) {
      $scope.groupGridApi = gridApi;
    },
    enableCellEditOnFocus: true
  };

  $scope.addField = function () {
    if (!("fields" in $scope.param.definition))
      $scope.param.definition.fields = [];
    $scope.param.definition.fields.push({
      type: 0,
      name: "",
      label: "",
      definition: {placeholder: 0}
    });
  };

  $scope.removeAllFields = function () {
    $scope.param.definition.fields = [];
  };

  $scope.removeSelectedFields = function () {
    var selectedRows = $scope.groupGridApi.selection.getSelectedRows();
    var rows = $scope.groupGridApi.grid.rows;
    for (var i = 0; i < selectedRows.length; i++) {
      for (var j = 0; j < rows.length; j++) {
        if (rows[j].entity.$$hashKey === selectedRows[i].$$hashKey) {
          $scope.removeField(j);
          break;
        }
      }
    }
  };

  $scope.removeField = function (index) {
    $scope.param.definition.fields.splice(index, 1);
  };

  $scope.$watch('param.definition.fields', function (newValue) {
    $scope.fields = newValue;
  });
};

concertoPanel.controller('WizardParamDefiner9Controller', ["$scope", "$filter", "uiGridConstants", "GridService", WizardParamDefiner9Controller]);