concertoPanel.factory('DataTableCollectionService', function (BaseCollectionService) {
    let collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.DATA_TABLE_COLLECTION;
    collectionService.userRoleRequired = "role_table";
    return collectionService;
});