/**
 * List
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter10Controller($scope, AdministrationSettingsService, uiGridConstants, GridService, $filter) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.gridService = GridService;

  $scope.listOptions = {
    //grid virtialization <-> setter directive workaround
    virtualizationThreshold: 64000,
    enableFiltering: false,
    enableGridMenu: true,
    exporterMenuCsv: false,
    exporterMenuPdf: false,
    importerShowMenu: false,
    data: "output",
    exporterCsvFilename: 'export.csv',
    exporterHeaderFilterUseName: true,
    gridMenuCustomItems: [
      {
        title: Trans.LIST_BUTTONS_TOGGLE_FILTERS,
        action: function ($event) {
          $scope.listOptions.enableFiltering = !$scope.listOptions.enableFiltering;
          $scope.listGridApi.core.notifyDataChange(uiGridConstants.dataChange.COLUMN);
        }
      }
    ],
    exporterHeaderFilter: function (name) {
      return name;
    },
    showGridFooter: true,
    columnDefs: [],
    onRegisterApi: function (gridApi) {
      $scope.listGridApi = gridApi;
    },
    importerDataAddCallback: function (grid, newObjects) {
      for (var i = 0; i < newObjects.length; i++) {
        for (var key in newObjects[i]) {
          for (var j = 0; j < $scope.listOptions.columnDefs.length; j++) {
            var col = $scope.listOptions.columnDefs[j];
            if (col.name === key) {
              if (col.type == 4) {
                newObjects[i][key] = newObjects[i][key].toString();
              }
              if (col.type == 7 || col.type == 9 || col.type == 10 || col.type == 12) {
                newObjects[i][key] = angular.fromJson(newObjects[i][key]);
              }
              break;
            }
          }
        }
        $scope.output.push(newObjects[i]);
      }
    },
    exporterFieldCallback: function (grid, row, col, value) {
      if (value !== undefined && value !== null && typeof value === 'object') {
        value = angular.toJson(value);
      }
      return value;
    },
    enableCellEditOnFocus: false
  };

  $scope.getColumnDefs = function (obj, param, parent, output, isGroupField) {
    if (!obj)
      return [];
    if (!isGroupField && obj.type == 9) {
      var cols = [];
      var fields = obj.definition.fields;
      for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var param = "grid.appScope.param.definition.element.definition.fields[" + i + "]";
        var parent = "grid.appScope.output[grid.appScope.output.indexOf(row.entity)]";
        var output = "grid.appScope.output[grid.appScope.output.indexOf(row.entity)]." + field.name;
        var add = $scope.getColumnDefs(field, param, parent, output, true);
        for (var j = 0; j < add.length; j++) {
          cols.push(add[j]);
        }
      }
      return $filter('orderBy')(cols, "+order");
    }

    return [{
      type: obj.type,
      order: obj.order,
      displayName: isGroupField ? obj.label : Trans.TEST_WIZARD_PARAM_LIST_COLUMN_ELEMENT,
      name: isGroupField ? obj.name : "value",
      cellTemplate:
      "<div class='ui-grid-cell-contents'>" +
      $scope.getParamSetterCellTemplate(param, parent, output) +
      "</div>"
    }];
  };

  $scope.initializeListColumnDefs = function () {
    var defs = [];
    var param = "grid.appScope.param.definition.element";
    var parent = "grid.appScope.output";
    var output = "grid.appScope.output[grid.appScope.output.indexOf(row.entity)].value";
    var cd = $scope.getColumnDefs($scope.param.definition.element, param, parent, output, false);
    for (var i = 0; i < cd.length; i++) {
      defs.push(cd[i]);
    }

    defs.push({
      displayName: "",
      name: "_action",
      enableSorting: false,
      enableFiltering: false,
      exporterSuppressExport: true,
      enableCellEdit: false,
      cellTemplate:
      "<div class='ui-grid-cell-contents' align='center'>" +
      '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeElement(grid.appScope.output.indexOf(row.entity));">' + Trans.TEST_WIZARD_PARAM_LIST_ELEMENT_DELETE + '</button>' +
      "</div>",
      width: 100
    });
    $scope.listOptions.columnDefs = defs;
  };

  $scope.moveElementUp = function (index) {
    $scope.output.splice(index + 1, 0, $scope.output.splice(index, 1)[0]);
  };
  $scope.moveElementDown = function (index) {
    $scope.output.splice(index - 1, 0, $scope.output.splice(index, 1)[0]);
  };

  //TODO Is this below needed?
  $scope.addElement = function () {
    if ($scope.param.definition.element.type == 4) {
      $scope.output.push({value: null});
    } else if ($scope.param.definition.element.type == 7 || $scope.param.definition.element.type == 9) {
      $scope.output.push({});
    } else if ($scope.param.definition.element.type == 10) {
      $scope.output.push([]);
    } else {
      $scope.output.push({value: null});
    }
  };
  $scope.removeElement = function (index) {
    $scope.output.splice(index, 1);
  };
  $scope.removeSelectedElements = function () {
    var selectedRows = $scope.listGridApi.selection.getSelectedRows();
    for (var i = 0; i < selectedRows.length; i++) {
      for (var j = 0; j < $scope.output.length; j++) {
        if ($scope.output[j] == selectedRows[i]) {
          $scope.removeElement(j);
          break;
        }
      }
    }
  };
  $scope.removeAllElements = function () {
    $scope.output.length = 0;
  };

  if ($scope.output === null || $scope.output === undefined || $scope.output.constructor !== Array) {
    $scope.output = [];
  }
  $scope.initializeListColumnDefs();

  $scope.$watch('param.definition.element.type', function (newValue, oldValue) {
    if (newValue === null || newValue === undefined)
      return;
    if (newValue != oldValue) {
      if ($scope.output === null || $scope.output === undefined || $scope.output.constructor !== Array || newValue !== oldValue) {
        $scope.output = [];
      }
    }
  });
};

concertoPanel.controller('WizardParamSetter10Controller', ["$scope", "AdministrationSettingsService", "uiGridConstants", "GridService", "$filter", WizardParamSetter10Controller]);