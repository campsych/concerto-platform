concertoPanel.factory('ScheduledTasksCollectionService', function ($http, $timeout, $uibModal, AllCollectionService) {
    return {
        collectionPath: Paths.ADMINISTRATION_TASKS_COLLECTION,
        collection: [],
        packagesCollection: [],
        ongoingScheduledTasks: [],
        ongoingScheduledTasksTimer: null,
        contentBlocked: false,
        fetchObjectCollection: function (callback) {
            let obj = this;
            $http({
                url: obj.collectionPath,
                method: "GET"
            }).then(function (httpResponse) {
                obj.collection = httpResponse.data;
                obj.packagesCollection = obj.filterPackagesCollection();
                obj.ongoingScheduledTasks = obj.filterOngoingTasksCollection();
                obj.contentBlocked = obj.isContentBlocked();

                if (obj.ongoingScheduledTasks.length > 0) {
                    obj.ongoingScheduledTasksTimer = $timeout(() => {
                        obj.fetchObjectCollection();
                    }, 10000);
                }

                if (callback)
                    callback.call(this);
            });
        },
        filterPackagesCollection: function () {
            let c = [];
            for (let i = 0; i < this.collection.length; i++) {
                let task = this.collection[i];
                if (task.type === 4)
                    c.push(task);
            }
            return c;
        },
        filterOngoingTasksCollection: function () {
            let c = [];
            for (let i = 0; i < this.collection.length; i++) {
                let task = this.collection[i];
                if (task.status <= 1) {
                    c.push(task);
                    let taskInfo = JSON.parse(task.info);
                    task.contentBlocked = taskInfo.content_block == 1;
                }
            }
            return c;
        },
        isContentBlocked: function () {
            for (let i = 0; i < this.ongoingScheduledTasks.length; i++) {
                let task = this.ongoingScheduledTasks[i];
                if (task.content_blocked) return true;
            }
            return false;
        },
        get: function (id) {
            for (let i = 0; i < this.collection.length; i++) {
                let obj = this.collection[i];
                if (obj.id == id)
                    return obj;
            }
            return null;
        },
        launchOngoingTaskDialog: function() {
            let modal = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'ongoing_scheduled_task_dialog.html',
                controller: OngoingScheduledTaskDialogController,
                size: "lg"
            });

            modal.result.then(function () {
                window.onbeforeunload = null;
                location.reload();
            }, function () {
            });
        }
    }
});