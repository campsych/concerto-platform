concertoPanel.factory('DataTableCollectionService', function (BaseCollectionService) {
    var collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.DATA_TABLE_COLLECTION;
    return collectionService;
});