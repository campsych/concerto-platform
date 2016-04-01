concertoPanel.factory('ViewTemplateCollectionService', function (BaseCollectionService) {
    var collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.VIEW_TEMPLATE_COLLECTION;
    return collectionService;
});