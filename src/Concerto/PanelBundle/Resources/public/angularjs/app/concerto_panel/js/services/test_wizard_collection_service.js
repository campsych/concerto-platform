concertoPanel.factory('TestWizardCollectionService', function (BaseCollectionService) {
    let collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.TEST_WIZARD_COLLECTION;
    collectionService.userRoleRequired = "role_wizard";

    collectionService.getParam = function (paramId) {
        for (let i = 0; i < this.collection.length; i++) {
            let wizard = this.collection[i];
            for (let j = 0; j < wizard.steps.length; j++) {
                let step = wizard.steps[j];
                for (let k = 0; k < step.params.length; k++) {
                    let param = step.params[k];
                    if (param.id === paramId)
                        return param;
                }
            }
        }
        return null;
    };

    return collectionService;
});