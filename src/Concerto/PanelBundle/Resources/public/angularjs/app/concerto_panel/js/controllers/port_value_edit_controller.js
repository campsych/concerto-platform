function PortValueEditController($scope, $uibModalInstance, $timeout, $http, RDocumentation, object, editable) {
  $scope.object = object;
  $scope.editable = editable;
  $scope.removable = $scope.canRemovePort($scope.collectionService.getNode(object.node), object);
  $scope.canBePointer = !$scope.isPortConnected(object) || object.type == 1;

  $scope.codeOptions = {
    lineWrapping: true,
    lineNumbers: true,
    mode: 'r',
    viewportMargin: Infinity,
    readOnly: !editable,
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
        $scope.codeOptions.hintOptions.functionIndex = data;
      }
    });
  } else {
    $scope.codeOptions.hintOptions.functionIndex = RDocumentation.functionIndex;
  }

  $scope.change = function () {
    $scope.object.defaultValue = "0";
    $uibModalInstance.close({
      action: "save",
      object: $scope.object
    });
  };

  $scope.hide = function() {
    $uibModalInstance.close({
      action: "hide",
      object: $scope.object
    });
  };

  $scope.reset = function () {
    $scope.object.defaultValue = "1";
    $uibModalInstance.close({
      action: "save",
      object: $scope.object
    });
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss(0);
  };

  $timeout(function () {
    $scope.codemirrorForceRefresh++;
  }, 20);
}