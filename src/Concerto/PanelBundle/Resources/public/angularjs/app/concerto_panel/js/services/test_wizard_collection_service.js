concertoPanel.factory('TestWizardCollectionService', function (BaseCollectionService) {
    var collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.TEST_WIZARD_COLLECTION;

    collectionService.getParam = function (paramId) {
        for (var i = 0; i < this.collection.length; i++) {
            var wizard = this.collection[i];
            for (var j = 0; j < wizard.steps.length; j++) {
                var step = wizard.steps[j];
                for (var k = 0; k < step.params.length; k++) {
                    var param = step.params[k];
                    if (param.id === paramId)
                        return param;
                }
            }
        }
        return null;
    };

    return collectionService;
});