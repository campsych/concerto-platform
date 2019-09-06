/**
 * R code
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter11Controller($scope, RDocumentation, $http) {

    $scope.RDocumentation = RDocumentation;
    $scope.codeEditorOptions = {
        lineWrapping: true,
        lineNumbers: true,
        mode: 'r',
        readOnly: !$scope.editable,
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

    $scope.onPrimitiveValueChange($scope.output);
}

concertoPanel.controller('WizardParamSetter11Controller', ["$scope", "RDocumentation", "$http", WizardParamSetter11Controller]);