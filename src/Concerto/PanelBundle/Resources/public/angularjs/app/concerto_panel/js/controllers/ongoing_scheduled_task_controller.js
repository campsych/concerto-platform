function OngoingScheduledTaskDialogController($scope, $uibModalInstance, ScheduledTasksCollectionService) {
    $scope.service = ScheduledTasksCollectionService;

    $scope.return = function () {
        $uibModalInstance.dismiss(0);
    };

    $scope.refresh = function () {
        $uibModalInstance.close(1);
    };
}