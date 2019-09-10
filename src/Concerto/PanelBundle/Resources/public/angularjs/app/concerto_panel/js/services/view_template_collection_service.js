concertoPanel.factory('ViewTemplateCollectionService', function (BaseCollectionService) {
    let collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.VIEW_TEMPLATE_COLLECTION;
    collectionService.userRoleRequired = "role_template";
    return collectionService;
});