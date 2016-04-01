concertoPanel.factory('UserCollectionService', function (BaseCollectionService) {
    var collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.USER_COLLECTION;
    return collectionService;
});