angular.module('concertoPanel').directive('wizardParamDefiner', ["$compile", "$templateCache", "$uibModal", "TestWizardParam", function ($compile, $templateCache, $uibModal, TestWizardParam) {
  return {
    restrict: 'E',
    scope: {
      wizardObject: "=",
      param: "=",
      typesCollection: "=types"
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

      element.html($templateCache.get("type_" + scope.param.type + "_definer.html"));
      $compile(element.contents())(scope);

      /*
      scope.$watch('param.type', function (newValue, oldValue) {
        if (!scope.param)
          return;
        if (newValue === null || newValue === undefined)
          return;

        if (newValue != oldValue) {
          switch (parseInt(newValue)) {
            case 0:
            case 1:
            case 2:
            case 5:
            case 6:
            case 8:
              scope.param.definition = {defvalue: ""};
              break;
            case 3:
              scope.param.definition = {options: [], defvalue: ""};
              break;
            case 4:
              scope.param.definition = {defvalue: "0"};
              break;
            case 9:
              scope.param.definition = {fields: []};
              break;
            case 10:
              scope.param.definition = {
                element: {
                  type: 0,
                  definition: {placeholder: 0}
                }
              };
              break;
            case 12:
              scope.param.definition = {cols: []};
              break;
            default:
              scope.param.definition = {placeholder: 0};
              break;
          }
        }

        element.html($templateCache.get("type_" + newValue + "_definer.html"));
        $compile(element.contents())(scope);
      });

      scope.$watch('param.definition.element.type', function (newValue, oldValue) {
        if (newValue === null || newValue === undefined)
          return;
        if (newValue != oldValue) {
          if (scope.param.type == 10) {
            scope.param.definition.element.definition = {placeholder: 0};
          }
        }
      });
       */
    }
  };
}]);