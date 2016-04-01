function StateViewController($scope, $state) {
    var id = $state.params.id;
    $scope.edit(id);
    if ($scope.object == null) {
        $scope.$watch("collectionService.collection", function () {
            $scope.edit(id);
        });
    }
}