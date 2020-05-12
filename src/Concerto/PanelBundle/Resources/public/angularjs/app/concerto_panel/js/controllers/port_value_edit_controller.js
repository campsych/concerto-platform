function PortValueEditController($scope, $uibModalInstance, $timeout, $http, TestCollectionService, RDocumentation, object, editable) {
  $scope.object = object;
  $scope.editable = editable;
  $scope.removable = $scope.canRemovePort($scope.collectionService.getNode(object.node), object);
  $scope.connected = $scope.isPortConnected(object);
  $scope.canBePointer = !$scope.connected || object.type == 1;
  $scope.variable = TestCollectionService.getVariable($scope.object.variable);

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
    $http.get(RDocumentation.rCacheDirectory + 'functionIndex.json').then(function (httpResponse) {
      if (httpResponse.data !== null) {
        RDocumentation.functionIndex = httpResponse.data;
        $scope.codeOptions.hintOptions.functionIndex = httpResponse.data;
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

  $scope.removeAllConnections = function() {
    $uibModalInstance.close({
      action: "removeConnections",
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