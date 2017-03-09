concertoPanel.factory('SessionCountCollectionService', function ($http) {
    return {
        collectionPath: Paths.ADMINISTRATION_SESSION_COUNT_COLLECTION,
        collection: [],
        fetchObjectCollection: function (filter, callback) {
            var obj = this;
            $http({
                url: obj.collectionPath.pf(angular.toJson(filter)),
                method: "GET"
            }).success(function (c) {
                obj.collection = c;
                if (callback)
                    callback.call(this);
            });
        },
        get: function (id) {
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                if (obj.id == id)
                    return obj;
            }
            return null;
        },
        getBy: function (field, value) {
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                if (obj[field] == value)
                    return obj;
            }
            return null;
        }
    };
});