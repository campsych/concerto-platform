function PortAddController($scope, $uibModalInstance, $http, TestCollectionService, node, connections, editable) {
  $scope.node = node;
  $scope.connections = connections;
  $scope.editable = editable;
  $scope.dynamicInputName = "";
  $scope.testCollectionService = TestCollectionService;

  $scope.isPortConnected = function (port) {
    for (var i = 0; i <connections.length; i++) {
      var conn = connections[i];
      if (conn.sourcePort === port.id || conn.destinationPort === port.id) {
        return true;
      }
    }
    return false;
  };

  $scope.getExposedPorts = function () {
    var result = [];
    for (var i = 0; i < $scope.node.ports.length; i++) {
      var port = $scope.node.ports[i]
      if (port.dynamic != 0) continue;
      if (port.exposed) result.push({
        id: port.id,
        exposed: port.exposed
      });
    }
    return result;
  };

  $scope.changeExposed = function () {
    $uibModalInstance.close({
      action: 0,
      node: node,
      exposedPorts: $scope.getExposedPorts()
    });
  };

  $scope.addDynamic = function () {
    $uibModalInstance.close({
      action: 1,
      node: node,
      name: $scope.dynamicInputName
    });
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss(0);
  };
}