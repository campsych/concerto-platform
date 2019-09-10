concertoPanel.factory('BaseCollectionService', function ($http, $filter, AuthService) {
    return {
        collectionInitialized: false,
        userRoleRequired: null,

        collectionPath: "",
        collection: [],
        fetchObjectCollection: function (params, callback, current) {
            if (!this.isAuthorized()) return false;

            let obj = this;
            if (current && this.collectionInitialized) {
                if (callback)
                    callback.call(this);
            } else {
                $http({
                    url: obj.collectionPath,
                    method: "GET",
                    // simple binding can't be used since sorting options need some processing before
                    params: params
                }).then(function (httpResponse) {
                    obj.collection = httpResponse.data;
                    obj.collectionInitialized = true;
                    if (callback)
                        callback.call(this);
                });
            }
        },
        get: function (id) {
            for (let i = 0; i < this.collection.length; i++) {
                let obj = this.collection[i];
                if (obj.id == id)
                    return obj;
            }
            return null;
        },
        getBy: function (field, value) {
            for (let i = 0; i < this.collection.length; i++) {
                let obj = this.collection[i];
                if (obj[field] == value)
                    return obj;
            }
            return null;
        },
        getUniqueTags: function () {
            let result = [];
            for (let i = 0; i < this.collection.length; i++) {
                let obj = this.collection[i];
                let tags = obj.tags.trim().split(" ");
                for (let j = 0; j < tags.length; j++) {
                    if (tags[j] && result.indexOf(tags[j]) === -1) {
                        result.push(tags[j]);
                    }
                }
            }
            return result;
        },
        getTaggedCollection: function (tag) {
            let result = [];
            for (let i = 0; i < this.collection.length; i++) {
                let obj = this.collection[i];
                let tags = obj.tags.trim().split(" ");
                for (let j = 0; j < tags.length; j++) {
                    if (tag == null || tags[j] === tag) {
                        result.push(obj);
                        break;
                    }
                }
            }
            return result;
        },
        isAuthorized: function () {
            if (this.userRoleRequired !== null) {
                if (AuthService.user.role_super_admin == 1) return true;
                if (AuthService.user[this.userRoleRequired] == 0) return false;
            }
            return true;
        }
    };
});
