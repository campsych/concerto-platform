function RDocumentationGenerationHelpController($scope, $uibModalInstance) {
    $scope.ok = function() {
        $uibModalInstance.dismiss(0);
    };
}