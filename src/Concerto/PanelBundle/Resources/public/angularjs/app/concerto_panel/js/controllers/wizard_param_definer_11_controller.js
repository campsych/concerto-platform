/**
 * R code
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner11Controller($scope, RDocumentation, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;

  $scope.codeEditorOptions = {
    lineWrapping: true,
    lineNumbers: true,
    mode: 'r',
    readOnly: $scope.wizardObject.starterContent && !AdministrationSettingsService.starterContentEditable,
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
};

concertoPanel.controller('WizardParamDefiner11Controller', ["$scope", "RDocumentation", "AdministrationSettingsService", WizardParamDefiner11Controller]);