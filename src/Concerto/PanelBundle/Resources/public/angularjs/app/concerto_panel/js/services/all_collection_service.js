concertoPanel.factory('AllCollectionService', function (DataTableCollectionService, TestCollectionService, TestWizardCollectionService, ViewTemplateCollectionService) {
    return {
        fetchAllCollections: function () {
            DataTableCollectionService.fetchObjectCollection();
            TestCollectionService.fetchObjectCollection();
            TestWizardCollectionService.fetchObjectCollection();
            ViewTemplateCollectionService.fetchObjectCollection();
        }
    };
});