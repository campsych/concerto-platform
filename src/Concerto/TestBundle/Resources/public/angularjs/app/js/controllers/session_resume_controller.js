'use strict';

function sessionResumeController($scope, $uibModalInstance) {
    $scope.startNewSession = false;
    
    $scope.ok = function() {
        $uibModalInstance.close($scope.startNewSession ? 0 : 1);
    };
}