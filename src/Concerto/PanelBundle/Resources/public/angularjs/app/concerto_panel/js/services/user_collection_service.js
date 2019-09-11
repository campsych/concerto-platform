concertoPanel.factory('UserCollectionService', function (BaseCollectionService) {
    let collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.USER_COLLECTION;
    collectionService.userRoleRequired = "role_super_admin";
    return collectionService;
});