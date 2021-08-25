function MfaEnabledController($scope, qrCode, secret, $uibModalInstance) {
    $scope.qrCode = qrCode;
    $scope.secret = secret;

    $scope.close = function () {
        $uibModalInstance.close();
    };
}