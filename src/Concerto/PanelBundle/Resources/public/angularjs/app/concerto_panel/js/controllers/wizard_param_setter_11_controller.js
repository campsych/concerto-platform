/**
 * R code
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter11Controller($scope, AdministrationSettingsService, RDocumentation, $http) {
  $scope.administrationSettingsService = AdministrationSettingsService;

  $scope.codeEditorOptions = {
    lineWrapping: true,
    lineNumbers: true,
    mode: 'r',
    readOnly: $scope.wizardObject.starterContent && !$scope.administrationSettingsService.starterContentEditable,
    viewportMargin: Infinity,
    hintOptions: {
      completeSingle: false,
      wizardService: RDocumentation
    },
    extraKeys: {
      "F11": function (cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      "Esc": function (cm) {
        if (cm.getOption("fullScreen"))
          cm.setOption("fullScreen", false);
      },
      "Ctrl-Space": "autocomplete"
    }
  };
  if (RDocumentation.functionIndex === null) {
    $http.get(RDocumentation.rCacheDirectory + 'functionIndex.json').success(function (data) {
      if (data !== null) {
        RDocumentation.functionIndex = data;
        $scope.codeEditorOptions.hintOptions.functionIndex = data;
      }
    });
  } else {
    $scope.codeEditorOptions.hintOptions.functionIndex = RDocumentation.functionIndex;
  }

  if ($scope.output === undefined || typeof $scope.output === 'object') {
    $scope.output = null;
  }
  if ($scope.output == null && $scope.param.definition != undefined) {
    $scope.output = $scope.param.definition.defvalue;
  }
  if ($scope.output === undefined || $scope.output === null) {
    $scope.output = "";
  }
  $scope.onPrimitiveValueChange($scope.output);
};

concertoPanel.controller('WizardParamSetter11Controller', ["$scope", "AdministrationSettingsService", "RDocumentation", "$http", WizardParamSetter11Controller]);