function DefaultRCompletionWizardController($scope, $uibModalInstance, $http, $timeout, RDocumentation, completionWidget, selection, completionContext, completionData) {

    // making sure that docs for used functions are loaded, except very strange occurences it'll be ready immediately
    RDocumentation.select(selection, function (ignored) {

        $scope.title = RDocumentation.getTitle();
        $scope.comment = RDocumentation.getComment();
        $scope.arguments = RDocumentation.getArguments();

    });

    $scope.miniCodemirror = {
        lineWrapping: false,
        lineNumbers: false,
        viewportMargin: Infinity,
        mode: 'r',
        /* autofocus: false,*/
        width: "400px"
    };

    $scope.insertComments = 1;
    $scope.autoFormat = 0;

    $scope.cancel = function () {
        $uibModalInstance.dismiss(0);
    };

    $scope.getCompletionData = function () {
        var result = '';
        if ($scope.insertComments)
            result += "# " + $scope.comment + "\n";
        result += RDocumentation.getFunctionName() + "(\n";

        var first = true;
        for (var itr = 0; itr < $scope.arguments.length; itr++) {

            var arg = $scope.arguments[ itr ];
            if (!arg.value || (arg.value == ''))
                continue;

            if (!first)
                result += ",\n";
            first = false;

            if ($scope.insertComments && (arg.comment))
                result += "    # " + arg.comment + "\n";
            result += "    " + arg.name + "=" + arg.value;
        }
        result += "\n)";

        return result;
    }




    $scope.insert = function () {
        $uibModalInstance.dismiss(0);

        // no reasonable solution other than $timeout for an issue with calling cm replace
        // http://stackoverflow.com/a/18996042
        var inserted = $scope.getCompletionData();
        $timeout(
                function () {
                    var from = completionContext.from || completionData.from;
                    completionWidget.cm.replaceRange(inserted, from,
                            completionContext.to || completionData.to, "complete");
                    if ($scope.autoFormat) {
                        // indentRange was dropped in Codmirror 3.X+ without any replacement, so a workaround is needed
                        var newlines = inserted.split("\n").length;
                        completionWidget.cm.doc.setSelection(from, CodeMirror.Pos(from.line + newlines, 0));
                        completionWidget.cm.indentSelection("smart");
                        completionWidget.cm.doc.setSelection(CodeMirror.Pos(0, 0), CodeMirror.Pos(0, 0));
                    }
                }

        );
    }
}