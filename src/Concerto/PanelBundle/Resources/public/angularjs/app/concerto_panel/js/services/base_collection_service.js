concertoPanel.factory('BaseCollectionService', function ($http, $filter) {
    return {
        collectionInitialized: false,
        collectionPath: "",
        collection: [],
        fetchObjectCollection: function (params) {
            var obj = this;
            $http({
                url: obj.collectionPath,
                method: "GET",
                // simple binding can't be used since sorting options need some processing before
                params: params
            }).success(function (c) {
                obj.collection = c;
                obj.collectionInitialized = true;
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
        },
        getUniqueTags: function () {
            var result = [];
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                var tags = obj.tags.trim().split(" ");
                for (var j = 0; j < tags.length; j++) {
                    if (tags[j] && result.indexOf(tags[j]) === -1) {
                        result.push(tags[j]);
                    }
                }
            }
            return result;
        },
        getTaggedCollection: function (tag) {
            var result = [];
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                var tags = obj.tags.trim().split(" ");
                for (var j = 0; j < tags.length; j++) {
                    if (tag == null || tags[j] === tag) {
                        result.push(obj);
                        break;
                    }
                }
            }
            return result;
        }
    };
});
