/**
 * R code
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner11Controller($scope, RDocumentation) {
    $scope.codeEditorOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'r',
        readOnly: !$scope.isEditable(),
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
        $http.get(RDocumentation.rCacheDirectory + 'functionIndex.json').then(function (httpResponse) {
            if (httpResponse.data !== null) {
                RDocumentation.functionIndex = httpResponse.data;
                $scope.codeEditorOptions.hintOptions.functionIndex = httpResponse.data;
            }
        });
    } else {
        $scope.codeEditorOptions.hintOptions.functionIndex = RDocumentation.functionIndex;
    }
}

concertoPanel.controller('WizardParamDefiner11Controller', ["$scope", "RDocumentation", WizardParamDefiner11Controller]);