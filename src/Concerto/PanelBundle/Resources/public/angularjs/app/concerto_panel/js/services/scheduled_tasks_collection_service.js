concertoPanel.factory('ScheduledTasksCollectionService', function ($http) {
    return {
        collectionPath: Paths.ADMINISTRATION_TASKS_COLLECTION,
        collection: [],
        packagesCollection: [],
        fetchObjectCollection: function (callback) {
            var obj = this;
            $http({
                url: obj.collectionPath,
                method: "GET"
            }).success(function (c) {
                obj.collection = c;
                obj.packagesCollection = obj.filterPackagesCollection();

                if (callback)
                    callback.call(this);
            });
        },
        filterPackagesCollection: function() {
            var c = [];
            for (var i = 0; i < this.collection.length; i++) {
                var task = this.collection[i];
                if (task.type === 4)
                    c.push(task);
            }
            return c;
        },
        get: function (id) {
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                if (obj.id == id)
                    return obj;
            }
            return null;
        }
    }
});