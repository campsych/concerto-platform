function PortInputAddController($scope, $uibModalInstance, $http, node, editable) {
  $scope.node = node;
  $scope.editable = editable;

  $scope.changeExposed = function () {
    $uibModalInstance.close($scope.node);
  };

  $scope.addDynamic = function () {
    $uibModalInstance.close($scope.node);
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss(0);
  };
}