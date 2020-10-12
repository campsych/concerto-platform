/**
 * List
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter10Controller($scope, AdministrationSettingsService, uiGridConstants, GridService, $filter, TestWizardParam) {
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
        enableHorizontalScrollbar: uiGridConstants.scrollbars.WHEN_NEEDED,
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
            for (let i = 0; i < newObjects.length; i++) {
                for (let j = 0; j < $scope.listOptions.columnDefs.length; j++) {
                    let col = $scope.listOptions.columnDefs[j];
                    if (typeof (col.param) === 'undefined') continue;
                    let found = false;
                    for (let key in newObjects[i]) {
                        if (col.name === key) {
                            found = true;
                            if (col.param.type == 4) {
                                newObjects[i][key] = newObjects[i][key].toString();
                            }
                            if (!TestWizardParam.isSimpleType(col.param.type)) {
                                newObjects[i][key] = angular.fromJson(newObjects[i][key]);
                            }
                            break;
                        }
                    }
                    if (!found) {
                        newObjects[i][col.name] = TestWizardParam.getParamOutputDefault(col.param);
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

    $scope.getColumnDefs = function (obj, param, parent, grandParent, output, isGroupField) {
        if (!obj)
            return [];
        if (!isGroupField && obj.type == 9) {
            let cols = [];
            let fields = obj.definition.fields;
            for (let i = 0; i < fields.length; i++) {
                let field = fields[i];
                let param = "grid.appScope.param.definition.element.definition.fields[" + i + "]";
                let parent = "grid.appScope.output[grid.appScope.output.indexOf(row.entity)]";
                let grandParent = "grid.appScope.parent";
                let output = "grid.appScope.output[grid.appScope.output.indexOf(row.entity)]." + field.name;
                let add = $scope.getColumnDefs(field, param, parent, grandParent, output, true);
                for (let j = 0; j < add.length; j++) {
                    cols.push(add[j]);
                }
            }
            return $filter('orderBy')(cols, "+order");
        }

        return [{
            param: obj,
            order: obj.order,
            displayName: isGroupField ? obj.label : Trans.TEST_WIZARD_PARAM_LIST_COLUMN_ELEMENT,
            name: isGroupField ? obj.name : "value",
            cellTemplate:
                "<div class='ui-grid-cell-contents'>" +
                $scope.getParamSetterCellTemplate(param, parent, grandParent, output) +
                "</div>"
        }];
    };

    $scope.initializeListColumnDefs = function () {
        let defs = [];
        let param = "grid.appScope.param.definition.element";
        let parent = "grid.appScope.output";
        let grandParent = "grid.appScope.parent";
        let output = TestWizardParam.isSimpleType($scope.param.definition.element.type) ? "grid.appScope.output[grid.appScope.output.indexOf(row.entity)].value" : "grid.appScope.output[grid.appScope.output.indexOf(row.entity)]";
        let cd = $scope.getColumnDefs($scope.param.definition.element, param, parent, grandParent, output, false);
        for (let i = 0; i < cd.length; i++) {
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
                '<button class="btn btn-danger btn-xs" ng-click="grid.appScope.removeElement(grid.appScope.output.indexOf(row.entity));" ng-disabled="!grid.appScope.editable">' + Trans.TEST_WIZARD_PARAM_LIST_ELEMENT_DELETE + '</button>' +
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

    $scope.addElement = function () {
        $scope.output.push(TestWizardParam.getParamOutputDefault($scope.param.definition.element));
        TestWizardParam.objectifyListElements($scope.param, $scope.output);
    };

    $scope.removeElement = function (index) {
        $scope.output.splice(index, 1);
    };
    $scope.removeSelectedElements = function () {
        let selectedRows = $scope.listGridApi.selection.getSelectedRows();
        for (let i = 0; i < selectedRows.length; i++) {
            for (let j = 0; j < $scope.output.length; j++) {
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

    $scope.initializeListColumnDefs();

    $scope.$watch('param.definition.element.type', function (newValue, oldValue) {
        if (newValue != oldValue) {
            $scope.output.length = 0;
        }
    });
}

concertoPanel.controller('WizardParamSetter10Controller', ["$scope", "AdministrationSettingsService", "uiGridConstants", "GridService", "$filter", "TestWizardParam", WizardParamSetter10Controller]);