concertoPanel.factory('TestCollectionService', function ($http, BaseCollectionService) {
    var collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.TEST_COLLECTION;

    collectionService.fetchLogsCollection = function (id) {
        var test = this.get(id);
        $http({
            url: Paths.TEST_LOG_COLLECTION.pf(id),
            method: "GET"
        }).success(function (c) {
            if (c.content) {
                test.logs = c.content;
            } else {
                test.logs = c;
            }
        });
    };

    collectionService.fetchNodesCollection = function (id, callback) {
        var test = this.get(id);
        $http({
            url: Paths.TEST_FLOW_NODE_COLLECTION.pf(id),
            method: "GET"
        }).success(function (c) {
            if (c.content) {
                test.nodes = c.content;
            } else {
                test.nodes = c;
            }
            if (callback)
                callback.call(this);
        });
    };

    collectionService.getPort = function (portId) {
        for (var i = 0; i < this.collection.length; i++) {
            var test = this.collection[i];
            for (var j = 0; j < test.nodes.length; j++) {
                var node = test.nodes[j];
                for (var k = 0; k < node.ports.length; k++) {
                    var port = node.ports[k];
                    if (port.id === portId)
                        return port;
                }
            }
        }
        return null;
    };

    collectionService.getNode = function (nodeId) {
        for (var i = 0; i < this.collection.length; i++) {
            var test = this.collection[i];
            for (var j = 0; j < test.nodes.length; j++) {
                var node = test.nodes[j];
                if (node.id === nodeId)
                    return node;
            }
        }
        return null;
    };

    collectionService.updateNode = function (newNode) {
        for (var i = 0; i < this.collection.length; i++) {
            var test = this.collection[i];
            for (var j = 0; j < test.nodes.length; j++) {
                var node = test.nodes[j];
                if (node.id === newNode.id) {
                    angular.merge(test.nodes[j], newNode);
                    return null;
                }
            }
        }
        return null;
    };

    collectionService.fetchNodesConnectionCollection = function (id, callback) {
        var test = this.get(id);
        $http({
            url: Paths.TEST_FLOW_CONNECTION_COLLECTION.pf(id),
            method: "GET"
        }).success(function (c) {
            if (c.content) {
                test.nodesConnections = c.content;
            } else {
                test.nodesConnections = c;
            }
            if (callback)
                callback.call(this);
        });
    };

    collectionService.getConnection = function (connectionId) {
        for (var i = 0; i < this.collection.length; i++) {
            var test = this.collection[i];
            for (var j = 0; j < test.nodesConnections.length; j++) {
                var connection = test.nodesConnections[j];
                if (connection.id === connectionId)
                    return connection;
            }
        }
        return null;
    };

    collectionService.fetchVariablesCollection = function (id) {
        var test = this.get(id);
        $http({
            url: Paths.TEST_VARIABLE_BY_TEST_COLLECTION.pf(id),
            method: "GET"
        }).success(function (c) {
            if (c.content) {
                test.variables = c.content;
            } else {
                test.variables = c;
            }
        });
    };

    return collectionService;
});