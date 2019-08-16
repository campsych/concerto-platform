angular.module('concertoPanel').directive('wizardParamDefiner', ["$compile", "$templateCache", "$uibModal", "TestWizardParam", function ($compile, $templateCache, $uibModal, TestWizardParam) {
    return {
        restrict: 'E',
        scope: {
            wizardObject: "=",
            param: "=",
            typesCollection: "=types",
            editable: "="
        },
        link: function (scope, element, attrs, controllers) {
            scope.testWizardParamService = TestWizardParam;

            scope.hasCustomDefiner = function () {
                if (!scope.param)
                    return false;
                return scope.typesCollection[scope.param.type].definer;
            };

            if (scope.param) {
                if (!("definition" in scope.param)) {
                    scope.param.definition = {placeholder: 0};
                }
            }

            scope.getParamDefinitionCellTemplate = function (param) {
                var cell = "";
                if (scope.typesCollection[param.type].definer) {
                    cell = "<i class='glyphicon glyphicon-align-justify clickable' ng-click='grid.appScope.launchDefinitionDialog(row.entity)' uib-tooltip-html='\"" + Trans.TEST_WIZARD_PARAM_DEFINITION_ICON_TOOLTIP + "\"' tooltip-append-to-body='true'></i>" +
                        '<span class="wizardParamSummary">{{grid.appScope.testWizardParamService.getDefinerSummary(row.entity)}}</span>';
                } else
                    cell = "-";
                return cell;
            };

            scope.launchDefinitionDialog = function (param) {
                $uibModal.open({
                    templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_definer_dialog.html",
                    scope: scope,
                    controller: TestWizardParamDefinerController,
                    resolve: {
                        param: function () {
                            return param;
                        },
                        typesCollection: function () {
                            return scope.typesCollection;
                        }
                    },
                    size: "prc-lg"
                });
            };

            //this function must stay for nested definers
            scope.isEditable = function () {
                return scope.editable;
            };

            element.html($templateCache.get("type_" + scope.param.type + "_definer.html"));
            $compile(element.contents())(scope);
        }
    };
}]);