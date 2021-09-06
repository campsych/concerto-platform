concertoPanel.factory('TestCollectionService', function ($http, BaseCollectionService) {
    let collectionService = Object.create(BaseCollectionService);
    collectionService.collectionPath = Paths.TEST_COLLECTION;
    collectionService.userRoleRequired = "role_test";

    collectionService.fetchLogsCollection = function (id) {
        let service = this;
        return new Promise((resolve, reject) => {
            let test = service.get(id);
            $http({
                url: Paths.TEST_LOG_COLLECTION.pf(id),
                method: "GET"
            }).then(function (httpResponse) {
                if (httpResponse.data.content) {
                    test.logs = httpResponse.data.content;
                } else {
                    test.logs = httpResponse.data;
                }
                resolve(test.logs);
            });
        });
    };

    collectionService.fetchNodesCollection = function (id) {
        let service = this;
        return new Promise((resolve, reject) => {
            let test = service.get(id);
            $http({
                url: Paths.TEST_FLOW_NODE_COLLECTION.pf(id),
                method: "GET"
            }).then(function (httpResponse) {
                if (httpResponse.data.content) {
                    test.nodes = httpResponse.data.content;
                } else {
                    test.nodes = httpResponse.data;
                }
                resolve(test.nodes);
            });
        });
    };

    collectionService.getPort = function (portId) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.nodes.length; j++) {
                let node = test.nodes[j];
                for (let k = 0; k < node.ports.length; k++) {
                    let port = node.ports[k];
                    if (port.id == portId)
                        return port;
                }
            }
        }
        return null;
    };

    collectionService.removePort = function (portId) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.nodes.length; j++) {
                let node = test.nodes[j];
                for (let k = 0; k < node.ports.length; k++) {
                    let port = node.ports[k];
                    if (port.id == portId) {
                        node.ports.splice(k, 1);
                    }
                }
            }
        }
    };

    collectionService.getNode = function (nodeId) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.nodes.length; j++) {
                let node = test.nodes[j];
                if (node.id == nodeId)
                    return node;
            }
        }
        return null;
    };

    collectionService.updateNode = function (newNode) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.nodes.length; j++) {
                let node = test.nodes[j];
                if (node.id == newNode.id) {
                    angular.merge(test.nodes[j], newNode);
                    return null;
                }
            }
        }
        return null;
    };

    collectionService.fetchNodesConnectionCollection = function (id) {
        let service = this;
        return new Promise((resolve, reject) => {
            let test = service.get(id);
            $http({
                url: Paths.TEST_FLOW_CONNECTION_COLLECTION.pf(id),
                method: "GET"
            }).then(function (httpResponse) {
                if (httpResponse.data.content) {
                    test.nodesConnections = httpResponse.data.content;
                } else {
                    test.nodesConnections = httpResponse.data;
                }
                resolve(test.nodesConnections);
            });
        });
    };

    collectionService.getConnection = function (connectionId) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.nodesConnections.length; j++) {
                let connection = test.nodesConnections[j];
                if (connection.id == connectionId)
                    return connection;
            }
        }
        return null;
    };

    collectionService.getVariable = function (id) {
        for (let i = 0; i < this.collection.length; i++) {
            let test = this.collection[i];
            for (let j = 0; j < test.variables.length; j++) {
                let variable = test.variables[j];
                if (variable.id == id) return variable;
            }
        }
        return null;
    };

    collectionService.fetchVariablesCollection = function (id) {
        let test = this.get(id);
        $http({
            url: Paths.TEST_VARIABLE_BY_TEST_COLLECTION.pf(id),
            method: "GET"
        }).then(function (httpResponse) {
            if (httpResponse.data.content) {
                test.variables = httpResponse.data.content;
            } else {
                test.variables = httpResponse.data;
            }
        });
    };

    return collectionService;
});