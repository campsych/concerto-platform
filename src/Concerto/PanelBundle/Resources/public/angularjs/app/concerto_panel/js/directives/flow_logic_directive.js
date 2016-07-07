'use strict';
$.fn.pannable = function () {
    var lastPosition = null;
    var position = null;
    var difference = null;
    $($(this).selector).on("mousedown mouseup mousemove", function (e) {
        window.cursorEvent = e;
        if (e.type == "mousedown") {
            window.mouseDown = true;
            lastPosition = [e.clientX, e.clientY];
        }
        if (e.button === 2) {
            window.rightClickEvent = e;
        }
        if (e.type == "mouseup")
            window.mouseDown = false;
        if (e.type == "mousemove" && window.mouseDown == true) {
            position = [e.clientX, e.clientY];
            difference = [(position[0] - lastPosition[0]), (position[1] - lastPosition[1])];
            $(this).scrollLeft($(this).scrollLeft() - difference[0]);
            $(this).scrollTop($(this).scrollTop() - difference[1]);
            lastPosition = [e.clientX, e.clientY];
        }
    });
};

angular.module('concertoPanel').directive('flowLogic', ['$http', '$compile', '$timeout', '$uibModal', '$filter', function ($http, $compile, $timeout, $uibModal, $filter) {
        return {
            restrict: 'A',
            link: function (scope, element, attrs, controllers) {
                scope.initialized = false;
                scope.refreshing = false;
                scope.flowScale = 1;
                scope.nodeContext = null;
                scope.currentMouseEvent = null;
                scope.selectedNodeIds = [];

                scope.resetView = function () {
                    for (var i = 0; i < scope.object.nodes.length; i++) {
                        var node = scope.object.nodes[i];
                        $("#flowContainerScroll").scrollLeft(node.posX * scope.flowScale);
                        $("#flowContainerScroll").scrollTop(node.posY * scope.flowScale);
                        break;
                    }
                };

                scope.setZoom = function (value, instance, el) {
                    if (scope.refreshing)
                        return;
                    var maxZoom = 1;
                    var minZoom = 0.1;
                    var zoomSteps = 25;
                    var zoom = value > 0 ? scope.flowScale + (maxZoom - minZoom) / zoomSteps : scope.flowScale - (maxZoom - minZoom) / zoomSteps;
                    zoom = Math.max(minZoom, zoom);
                    zoom = Math.min(maxZoom, zoom);

                    var transformOrigin = [0, 0];
                    instance = instance || jsPlumb;
                    el = el || instance.getContainer();
                    var p = ["webkit", "moz", "ms", "o"],
                            s = "scale(" + zoom + ")",
                            oString = (transformOrigin[0] * 100) + "% " + (transformOrigin[1] * 100) + "%";
                    for (var i = 0; i < p.length; i++) {
                        el.style[p[i] + "Transform"] = s;
                        el.style[p[i] + "TransformOrigin"] = oString;
                    }

                    el.style["transform"] = s;
                    el.style["transformOrigin"] = oString;
                    instance.setZoom(zoom);

                    $("#flowContainerScroll").scrollLeft($("#flowContainerScroll")[0].scrollLeft * (zoom / scope.flowScale));
                    $("#flowContainerScroll").scrollTop($("#flowContainerScroll")[0].scrollTop * (zoom / scope.flowScale));
                    scope.flowScale = zoom;
                };

                scope.onFlowCtxOpened = function () {
                    scope.selectedNodeIds = [];
                };

                scope.onNodeCtxOpened = function (nodeId) {
                    for (var i = 0; i < scope.object.nodes.length; i++) {
                        var node = scope.object.nodes[i];
                        if (nodeId === node.id)
                            scope.nodeContext = node;
                    }
                };

                scope.truncateNodeName = function (name) {
                    if (name.length > 25) {
                        name = name.substr(0, 11) + "..." + name.substr(name.length - 11, 11);
                    }
                    return name;
                };

                scope.toggleNodeSelection = function (id, ignoreCtrl) {
                    if (window.cntrlIsPressed || ignoreCtrl) {
                        var index = scope.selectedNodeIds.indexOf(id);
                        if (index === -1) {
                            scope.selectedNodeIds.push(id);
                        } else {
                            scope.selectedNodeIds.splice(index, 1);
                        }
                    }
                };

                scope.drawNode = function (node) {
                    /* SETTINGS START */
                    var portTopMargin = 40;
                    var portElemMargin = 30;
                    var inputPortOverlayLocation = [3.4, 0.5];
                    var inPortOverlayLocation = [3.7, 0.5];
                    var returnPortOverlayLocation = [-2.4, 0.5];
                    var branchPortOverlayLocation = [-2.7, 0.5];
                    /* SETTINGS END */

                    node.ports = $filter('orderBy')(node.ports, "variableObject.name");

                    var tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='collectionService.getNode(" + node.id + ").sourceTestDescription'></i>";
                    var fullName = "";
                    var name = "";
                    var nodeClass = "";
                    if (node.type === 1) {
                        fullName = Trans.TEST_FLOW_NODE_NAME_START;
                        name = scope.truncateNodeName(fullName);
                        tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_FLOW_NODE_DESCRIPTION_START + "\"'></i>";
                        nodeClass = "nodeStart";
                    } else if (node.type === 2) {
                        fullName = Trans.TEST_FLOW_NODE_NAME_END;
                        name = scope.truncateNodeName(fullName);
                        tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_FLOW_NODE_DESCRIPTION_END + "\"'></i>";
                        nodeClass = "nodeEnd";
                    } else if (node.type === 0) {
                        fullName = node.sourceTestName;
                        name = scope.truncateNodeName(fullName);
                        var test = scope.collectionService.get(node.sourceTest);
                        if (test.sourceWizard) {
                            name = "<a href='#' ng-click='editNodeWizard(collectionService.getNode(" + node.id + "), collectionService.get(" + node.sourceTest + "))'>" + name + "</a>";
                        }
                    }

                    var elemHtml = "<div context-menu='onNodeCtxOpened(" + node.id + ")' data-target='menu-node' id='node" + node.id + "' class='node " + nodeClass + "' ng-class='{\"node-selected\": selectedNodeIds.indexOf(" + node.id + ")!==-1}' style='top:" + node.posY + "px; left:" + node.posX + "px;' ng-click='toggleNodeSelection(" + node.id + ")'>";
                    if (node.type === 1 || node.type === 2) {
                        elemHtml = "<div id='node" + node.id + "' class='node " + nodeClass + "' style='top:" + node.posY + "px; left:" + node.posX + "px;'>";
                    }
                    elemHtml +=
                            "<div class='nodeHeader' tooltip-append-to-body='true' uib-tooltip-html='\"" + fullName + "\"'>" + tooltip + name + "</div>" +
                            "<div class='nodeFooter'>" +
                            "<div style='display: table; margin: auto;'>" +
                            "<i class='glyphicon clickable' ng-class='{\"glyphicon-arrow-up\": collectionService.getNode(" + node.id + ").expanded, \"glyphicon-arrow-down\": !collectionService.getNode(" + node.id + ").expanded}' " +
                            "ng-click='toggleUnconnectedPortsCollapse(" + node.id + ")' " +
                            "tooltip-placement='bottom' tooltip-append-to-body='false' uib-tooltip='" + Trans.TEST_FLOW_BUTTONS_TOGGLE_COLLAPSE_TOOLTIP + "'></i></div>" +
                            "</div>" +
                            "</div>";
                    var elem = $(elemHtml).appendTo("#flowContainer");
                    var leftCount = 0;
                    var rightCount = 0;
                    //in port
                    if (node.type !== 1) {
                        jsPlumb.addEndpoint(elem, {
                            uuid: "node" + node.id + "-ep_entry",
                            isTarget: true,
                            maxConnections: -1,
                            endpoint: "Rectangle",
                            anchor: [0, 0, -1, 0, 0, portTopMargin + leftCount * portElemMargin],
                            paintStyle: {fillStyle: "white", strokeStyle: "grey"},
                            overlays: [
                                ["Custom", {
                                        create: function (component) {
                                            var tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_FLOW_PORT_DESCRIPTION_IN + "\"'></i>";
                                            var overlayElem = $("<div>" +
                                                    "<div class='portLabel portLabelIn'>" + tooltip + Trans.TEST_FLOW_PORT_NAME_IN + "</div>" +
                                                    "</div>");
                                            $compile(overlayElem)(scope);
                                            return overlayElem;
                                        },
                                        location: inPortOverlayLocation,
                                        id: "overlayIn" + node.id
                                    }]
                            ],
                            parameters: {
                                targetNode: node,
                                targetPort: null
                            }
                        });
                        leftCount++;
                    }

                    if (node.type !== 2) {
                        for (var i = 0; i < node.ports.length; i++) {
                            var port = node.ports[i];
                            if (port.variableObject.type === 2) { //branches
                                jsPlumb.addEndpoint(elem, {
                                    uuid: "node" + node.id + "-ep" + port.id,
                                    isSource: true,
                                    maxConnections: 1,
                                    endpoint: "Rectangle",
                                    anchor: [1, 0, 1, 0, 0, portTopMargin + rightCount * portElemMargin],
                                    paintStyle: {fillStyle: "orange", strokeStyle: "grey"},
                                    overlays: [
                                        ["Custom", {
                                                create: function (component) {
                                                    var portId = component._jsPlumb.parameters.sourcePort.id;
                                                    var varName = component._jsPlumb.parameters.sourcePort.variableObject.name;
                                                    var tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='collectionService.getPort(" + portId + ").variableObject.description'></i>";
                                                    var overlayElem = $("<div>" +
                                                            "<div class='portLabel portLabelBranch'>" + varName + tooltip + "</div>" +
                                                            "</div>");
                                                    $compile(overlayElem)(scope);
                                                    return overlayElem;
                                                },
                                                location: branchPortOverlayLocation,
                                                id: "overlay" + port.id
                                            }]
                                    ],
                                    parameters: {
                                        sourceNode: node,
                                        sourcePort: port
                                    }
                                });
                                rightCount++;
                            }
                        }
                    }

                    for (var i = 0; i < node.ports.length; i++) {
                        var port = node.ports[i];
                        if (scope.isPortVisible(node, port) && ((node.type === 0 && port.variableObject.type === 0) || (node.type === 2 && port.variableObject.type === 1))) { //input param
                            jsPlumb.addEndpoint(elem, {
                                uuid: "node" + node.id + "-ep" + port.id,
                                maxConnections: -1,
                                isTarget: true,
                                endpoint: "Dot",
                                anchor: [0, 0, -1, 0, 0, portTopMargin + leftCount * portElemMargin],
                                paintStyle: {fillStyle: "blue", strokeStyle: "grey"},
                                overlays: [[
                                        "Custom", {
                                            create: function (component) {
                                                var portId = component._jsPlumb.parameters.targetPort.id;
                                                var varName = component._jsPlumb.parameters.targetPort.variableObject.name;
                                                var tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='collectionService.getPort(" + portId + ").variableObject.description'></i>";
                                                var overlayElem = $("<div>" +
                                                        "<div " +
                                                        "ng-class='{\"portLabel\": true, \"portLabelInput\": true, \"portLabelInputString\": collectionService.getPort(" + portId + ").string === \"1\"}'" +
                                                        ">" + tooltip + "<span uib-tooltip-html='\"" + Trans.TEST_FLOW_PORT_INPUT_LABEL_TOOLTIP + "\"' tooltip-append-to-body='true'>" + varName + "</span></div>" +
                                                        "</div>");
                                                $compile(overlayElem)(scope);
                                                return overlayElem;
                                            },
                                            location: inputPortOverlayLocation,
                                            id: "overlay" + port.id
                                        }], [
                                        "Custom", {
                                            create: function (component) {
                                                var portId = component._jsPlumb.parameters.targetPort.id;
                                                var connected = false;
                                                for (var j = 0; j < scope.object.nodesConnections.length; j++) {
                                                    var connection = scope.object.nodesConnections[j];
                                                    if (connection.destinationPort == portId) {
                                                        connected = true;
                                                        break;
                                                    }
                                                }

                                                var overlayElem = $("<div id='divPortControl" + portId + "' style='display:" + (connected ? "none" : "") + ";'>" +
                                                        "<i ng-class='{\"glyphInteractable\": true, \"glyphicon\": true, \"glyphicon-align-justify\": true, \"portValueDefault\": collectionService.getPort(" + portId + ").defaultValue == \"1\"}' " +
                                                        "ng-click='editPortCode(collectionService.getPort(" + portId + "))' " +
                                                        "uib-tooltip-html='collectionService.getPort(" + portId + ").value' tooltip-append-to-body='true'></i></div>");
                                                $compile(overlayElem)(scope);
                                                return overlayElem;
                                            },
                                            location: [-0.5, 0.5],
                                            id: "overlayCode" + port.id
                                        }
                                    ]],
                                parameters: {
                                    targetNode: node,
                                    targetPort: port
                                }
                            });
                            leftCount++;
                        } else if (scope.isPortVisible(node, port) && ((node.type === 0 && port.variableObject.type === 1) || (node.type === 1 && port.variableObject.type === 0))) { //return vars
                            jsPlumb.addEndpoint(elem, {
                                uuid: "node" + node.id + "-ep" + port.id,
                                isSource: true,
                                maxConnections: -1,
                                endpoint: "Dot",
                                anchor: [1, 0, 1, 0, 0, portTopMargin + rightCount * portElemMargin],
                                paintStyle: {fillStyle: "red", strokeStyle: "grey"},
                                overlays: [
                                    ["Custom", {
                                            create: function (component) {
                                                var portId = component._jsPlumb.parameters.sourcePort.id;
                                                var varName = component._jsPlumb.parameters.sourcePort.variableObject.name;
                                                var tooltip = "<i class='glyphicon glyphicon-question-sign' tooltip-append-to-body='true' uib-tooltip-html='collectionService.getPort(" + portId + ").variableObject.description'></i>";
                                                var overlayElem = $("<div>" +
                                                        "<div class='portLabel portLabelReturn'>" + varName + tooltip + "</div>" +
                                                        "</div>");
                                                $compile(overlayElem)(scope);
                                                return overlayElem;
                                            },
                                            location: returnPortOverlayLocation,
                                            id: "overlay" + port.id
                                        }]
                                ],
                                parameters: {
                                    sourceNode: node,
                                    sourcePort: port
                                }
                            });
                            rightCount++;
                        }
                    }

                    elem.css("height", (portTopMargin + Math.max(leftCount, rightCount) * portElemMargin) + "px");
                    jsPlumb.draggable(elem, {
                        containment: true,
                        stop: function (event, ui) {
                            var x = elem.position().left / scope.flowScale;
                            var y = elem.position().top / scope.flowScale;
                            $http.post(Paths.TEST_FLOW_NODE_SAVE.pf(node.id), {
                                "type": node.type,
                                "flowTest": scope.object.id,
                                "sourceTest": node.sourceTest,
                                "posX": x,
                                "posY": y
                            }).success(function (data) {
                                if (data.result === 0) {
                                    node.posX = x;
                                    node.posY = y;
                                }
                            });
                        }
                    });
                    $compile(elem)(scope);
                };

                scope.toggleInputEval = function (port) {
                    port.string = port.string === "1" ? "0" : "1"
                    $http.post(Paths.TEST_FLOW_PORT_SAVE.pf(port.id), {
                        "node": port.node,
                        "variable": port.variable,
                        "value": port.value,
                        "string": port.string,
                        "default": port.defaultValue
                    }).success(function (data) {
                    });
                };

                scope.excludeSelfFilter = function (value, index, array) {
                    return value.name != scope.object.name;
                }

                scope.editNodeWizard = function (node, test) {
                    var oldValue = angular.copy(node);
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "node_wizard_dialog.html",
                        controller: NodeWizardController,
                        scope: scope,
                        resolve: {
                            node: function () {
                                return node;
                            },
                            test: function () {
                                var copiedTest = angular.copy(test);
                                copiedTest.initProtected = "0";
                                return copiedTest;
                            }
                        },
                        size: "prc-lg"
                    });

                    modalInstance.result.then(function (response) {
                        $http.post(Paths.TEST_FLOW_PORT_SAVE_COLLECTION, {
                            "serializedCollection": angular.toJson(response.ports)
                        }).success(function (data) {
                            //scope.collectionService.updateNode(response);
                        });
                    }, function () {
                        scope.collectionService.updateNode(oldValue);
                    });
                };

                scope.editPortCode = function (port) {
                    var oldValue = port.value;
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "port_value_dialog.html",
                        controller: PortValueEditController,
                        scope: scope,
                        resolve: {
                            object: function () {
                                return port;
                            }
                        },
                        size: "prc-lg"
                    });

                    modalInstance.result.then(function (response) {
                        $http.post(Paths.TEST_FLOW_PORT_SAVE.pf(response.id), {
                            "node": response.node,
                            "variable": response.variable,
                            "value": response.value,
                            "string": response.string,
                            "default": response.defaultValue
                        }).success(function (data) {
                            port.value = data.object.value;
                        });
                    }, function () {
                        port.value = oldValue;
                    });
                };

                scope.editConnectionCode = function (connection) {
                    var oldValue = connection.returnFunction;
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "codemirror_dialog.html",
                        controller: CodemirrorController,
                        scope: scope,
                        resolve: {
                            value: function () {
                                return connection.returnFunction;
                            },
                            title: function () {
                                return connection.sourcePortObject.variableObject.name + "->" + connection.destinationPortObject.variableObject.name;
                            },
                            tooltip: function () {
                                return Trans.TEST_FLOW_RETURN_FUNCTION_TOOLTIP;
                            }
                        },
                        size: "lg"
                    });

                    modalInstance.result.then(function (newVal) {
                        connection.returnFunction = newVal;
                        $http.post(Paths.TEST_FLOW_CONNECTION_SAVE.pf(connection.id), {
                            "flowTest": connection.flowTest,
                            "sourceNode": connection.sourceNode,
                            "sourcePort": connection.sourcePort,
                            "destinationNode": connection.destinationNode,
                            "destinationPort": connection.destinationPort,
                            "returnFunction": connection.returnFunction
                        }).success(function (data) {
                        });
                    }, function () {
                        connection.returnFunction = oldValue;
                    });
                };

                scope.addNewNode = function (type, testId) {
                    var posX = window.rightClickEvent.offsetX;
                    var posY = window.rightClickEvent.offsetY;
                    if (testId == null)
                        testId = scope.object.id;
                    $http.post(Paths.TEST_FLOW_NODE_ADD_COLLECTION.pf(scope.object.id), {
                        "type": type,
                        "flowTest": scope.object.id,
                        "sourceTest": testId,
                        "posX": posX,
                        "posY": posY
                    }).success(function (data) {
                        if (data.result === 0) {
                            //scope.drawNode(data.object);

                            scope.object.nodes = data.collections.nodes;

                            if (data.object.sourceTestObject && data.object.sourceTestObject.sourceWizard) {
                                scope.editNodeWizard(scope.collectionService.getNode(data.object.id), data.object.sourceTestObject);
                            }
                        }
                    });
                };
                scope.removeNode = function (id) {
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
                        controller: ConfirmController,
                        size: "sm",
                        resolve: {
                            title: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_REMOVE_TITLE;
                            },
                            content: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_REMOVE_MESSAGE;
                            }
                        }
                    });

                    modalInstance.result.then(function (response) {
                        var node = null;
                        for (var i = 0; i < scope.object.nodes.length; i++) {
                            if (id === scope.object.nodes[i].id)
                                node = scope.object.nodes[i];
                        }

                        for (var i = 0; i < scope.object.nodesConnections.length; i++) {
                            var connection = scope.object.nodesConnections[i];
                            if (id === connection.sourceNode || id === connection.destinationNode) {
                                connection.removed = true;
                            }
                        }

                        $http.post(Paths.TEST_FLOW_NODE_DELETE_COLLECTION.pf(id), {
                        }).success(function (data) {
                            if (data.result === 0) {
                                jsPlumb.remove("node" + id);

                                scope.object.nodes = data.collections.nodes;
                                scope.object.nodesConnections = data.collections.nodesConnections;
                            }
                        });
                    });
                };

                scope.removeSelectedNodes = function () {
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'confirmation_dialog.html',
                        controller: ConfirmController,
                        size: "sm",
                        resolve: {
                            title: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_REMOVE_SELECTION_TITLE;
                            },
                            content: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_REMOVE_SELECTION_MESSAGE;
                            }
                        }
                    });

                    modalInstance.result.then(function (response) {
                        for (var id in scope.selectedNodeIds) {
                            var node = null;
                            for (var i = 0; i < scope.object.nodes.length; i++) {
                                if (id === scope.object.nodes[i].id)
                                    node = scope.object.nodes[i];
                            }

                            for (var i = 0; i < scope.object.nodesConnections.length; i++) {
                                var connection = scope.object.nodesConnections[i];
                                if (id === connection.sourceNode || id === connection.destinationNode) {
                                    connection.removed = true;
                                }
                            }
                        }

                        $http.post(Paths.TEST_FLOW_NODE_DELETE_COLLECTION.pf(scope.selectedNodeIds.join()), {
                        }).success(function (data) {
                            if (data.result === 0) {
                                for (var id in scope.selectedNodeIds) {
                                    jsPlumb.remove("node" + id);
                                }

                                scope.object.nodes = data.collections.nodes;
                                scope.object.nodesConnections = data.collections.nodesConnections;
                            }
                        });
                    });
                };

                scope.addConnection = function (concertoConnection, jspConnection) {
                    var params = jspConnection.getParameters();
                    $http.post(Paths.TEST_FLOW_CONNECTION_ADD_COLLECTION.pf(scope.object.id), {
                        "flowTest": scope.object.id,
                        "sourceNode": params.sourceNode.id,
                        "sourcePort": params.sourcePort.id,
                        "destinationNode": params.targetNode.id,
                        "destinationPort": params.targetPort ? params.targetPort.id : null
                    }).success(function (data) {
                        if (data.result === 0) {
                            scope.object.nodesConnections = data.collections.nodesConnections;
                            jspConnection.setParameter("concertoConnection", data.object);
                            scope.setUpConnection(jspConnection);
                        }
                    });
                };

                scope.saveConnection = function (concertoConnection, jspConnection) {
                    var id = 0;
                    if (concertoConnection)
                        id = concertoConnection.id;

                    var params = jspConnection.getParameters();
                    $http.post(Paths.TEST_FLOW_CONNECTION_SAVE.pf(id), {
                        "flowTest": scope.object.id,
                        "sourceNode": params.sourceNode.id,
                        "sourcePort": params.sourcePort.id,
                        "destinationNode": params.targetNode.id,
                        "destinationPort": params.targetPort ? params.targetPort.id : null
                    }).success(function (data) {
                        if (data.result === 0) {
                            jspConnection.setParameter("concertoConnection", data.object);
                            scope.setUpConnection(jspConnection);
                        }
                    });
                };

                scope.connect = function (concertoConnection) {
                    jsPlumb.connect({
                        uuids: [
                            "node" + concertoConnection.sourceNode + "-ep" + (concertoConnection.sourcePort ? concertoConnection.sourcePort : "_entry"),
                            "node" + concertoConnection.destinationNode + "-ep" + (concertoConnection.destinationPort ? concertoConnection.destinationPort : "_entry"),
                        ],
                        parameters: {
                            concertoConnection: concertoConnection
                        },
                        paintStyle: {dashstyle: "dot", strokeStyle: scope.getConnectionStrokeStyle(concertoConnection.automatic, concertoConnection.sourcePortObject.variableObject.type), lineWidth: scope.getConnectionLineWidth(concertoConnection.sourcePortObject.variableObject.type)}
                    });
                };

                scope.getConnectionStrokeStyle = function (automatic, type) {
                    switch (type) {
                        //in - out
                        case 2:
                            return "#858C8F";
                            //params
                        case 1:
                        default:
                            return "#CCD5D9";
                    }
                };

                scope.getConnectionLineWidth = function (type) {
                    switch (type) {
                        //in - out
                        case 2:
                            return 3;
                            //params
                        case 1:
                        default:
                            return 1;
                    }
                };

                scope.setUpConnection = function (jspConnection) {
                    var params = jspConnection.getParameters();
                    if (params.targetPort)
                        $("#divPortControl" + params.targetPort.id).hide();
                    if ((params.sourceNode.type === 1 && params.sourcePort.variableObject.type === 0) || (params.sourceNode.type === 0 && params.sourcePort.variableObject.type === 1)) {
                        jspConnection.addOverlay(
                                ["Custom", {
                                        create: function (component) {
                                            var overlayElem = $("<div>" +
                                                    "<div id='divConnectionControl" + params.concertoConnection.id + "'>" +
                                                    "<i class='glyphInteractable glyphicon glyphicon-align-justify' " +
                                                    "ng-click='editConnectionCode(collectionService.getConnection(" + params.concertoConnection.id + "))' " +
                                                    "uib-tooltip-html='collectionService.getConnection(" + params.concertoConnection.id + ").returnFunction' tooltip-append-to-body='true'></i></div>" +
                                                    "</div>");
                                            $compile(overlayElem)(scope);
                                            return overlayElem;
                                        },
                                        location: 0.5,
                                        id: "overlayConnection" + params.concertoConnection.id
                                    }]);
                    } else if (params.sourcePort.variableObject.type === 2) {
                        jspConnection.addOverlay(
                                ["Arrow", {location: 0.5, paintStyle: {fillStyle: "orange", strokeStyle: "grey"}}]);
                    }
                };

                scope.removeConnection = function (id) {
                    for (var i = 0; i < scope.object.nodesConnections.length; i++) {
                        var connection = scope.object.nodesConnections[i];
                        if (id === connection.id && connection.removed) {
                            return;
                        }
                    }

                    $http.post(Paths.TEST_FLOW_CONNECTION_DELETE_COLLECTION.pf(id), {
                    }).success(function (data) {
                        if (data.result === 0) {
                            scope.object.nodesConnections = data.collections.nodesConnections;
                        }
                    });
                };

                scope.toggleUnconnectedPortsCollapse = function (nodeId) {
                    var expanded = !scope.collectionService.getNode(nodeId).expanded;
                    scope.collectionService.getNode(nodeId).expanded = expanded;

                    scope.refreshFlow();
                };

                scope.isPortVisible = function (node, port) {
                    //input
                    if ((node.type === 0 && port.variableObject.type === 0) || (node.type === 2 && port.variableObject.type === 1)) {
                        if (node.expanded || scope.isPortConnected(port))
                            return true;
                    }
                    //returns
                    if ((node.type === 0 && port.variableObject.type === 1) || (node.type === 1 && port.variableObject.type === 0)) {
                        if (node.expanded || scope.isPortConnected(port))
                            return true;
                    }
                    return false;
                };

                scope.isPortConnected = function (port) {
                    for (var i = 0; i < scope.object.nodesConnections.length; i++) {
                        var conn = scope.object.nodesConnections[i];
                        if (conn.sourcePort === port.id || conn.destinationPort === port.id)
                            return true;
                    }
                    return false;
                };

                jsPlumb.setContainer($("#flowContainer"));

                scope.refreshFlow = function () {
                    scope.refreshing = true;
                    scope.selectedNodeIds = [];
                    jsPlumb.unbind('beforeDrop');
                    jsPlumb.unbind('connection');
                    jsPlumb.unbind('connectionMoved');
                    jsPlumb.unbind('connectionDetached');
                    jsPlumb.deleteEveryEndpoint();

                    $("#flowContainer .node").remove();

                    jsPlumb.bind("beforeDrop", function (info) {
                        if (!info.dropEndpoint || info.connection.endpoints.length === 0)
                            return false;

                        var sourceParams = info.connection.endpoints[0].getParameters();
                        var targetParams = info.dropEndpoint.getParameters();
                        var sourcePortType = sourceParams.sourcePort.variableObject.type;
                        var targetPortType = null;
                        if (targetParams.targetPort && targetParams.targetPort.variableObject)
                            targetPortType = targetParams.targetPort.variableObject.type;
                        var sourceNodeType = sourceParams.sourceNode.type;
                        var targetNodeType = targetParams.targetNode.type;
                        if (sourceNodeType === 1) {
                            if (sourcePortType === 0)
                                sourcePortType = 1;
                        }
                        if (targetNodeType === 2) {
                            if (targetPortType === 1)
                                targetPortType = 0;
                        }

                        switch (sourcePortType) {
                            //return
                            case 1:
                                if (targetPortType !== 0)
                                    return false;
                                break;
                                //branch
                            case 2:
                                if (targetPortType !== null)
                                    return false;
                                break;
                        }
                        return true;
                    });

                    jsPlumb.bind("connection", function (info) {
                        var params = info.connection.getParameters();
                        if (!params.concertoConnection) {
                            scope.addConnection(params.concertoConnection, info.connection);
                            return;
                        }
                        scope.setUpConnection(info.connection);
                    });

                    jsPlumb.bind("connectionMoved", function (info) {
                        var params = info.connection.getParameters();
                        scope.saveConnection(params.concertoConnection, info.connection);
                    });

                    jsPlumb.bind("connectionDetached", function (info) {
                        var params = info.connection.getParameters();
                        if (!params.concertoConnection)
                            return;
                        scope.removeConnection(params.concertoConnection.id);
                        if (params.targetPort)
                            $("#divPortControl" + params.targetPort.id).show();
                    });

                    $timeout(function () {
                        if (!scope.object.nodes)
                            return;
                        jsPlumb.setSuspendDrawing(true);
                        for (var i = 0; i < scope.object.nodes.length; i++) {
                            scope.drawNode(scope.object.nodes[i]);
                        }
                        for (var i = 0; i < scope.object.nodesConnections.length; i++) {
                            scope.connect(scope.object.nodesConnections[i]);
                        }
                        jsPlumb.setSuspendDrawing(false, true);
                        if (!scope.initialized) {
                            scope.initialized = true;
                            scope.resetView();
                        }
                        scope.refreshing = false;
                        jsPlumb.setZoom(scope.flowScale);
                    }, 1);
                };

                $(function () {
                    $("#flowContainerScroll").pannable();
                    $('#flowContainer').mousewheel(function (event) {
                        scope.setZoom(event.deltaY);
                        return false;
                    });
                    $('#flowContainer').mousemove(function (event) {
                        scope.currentMouseEvent = event;
                    });
                });

                scope.$watchCollection(
                        "[ object.nodes, object.nodesConnections ]",
                        function () {
                            if (scope.object.nodes.length > 0) {
                                scope.refreshFlow();
                            }
                        }
                );

                scope.$watch("object.id", function () {
                    scope.initialized = false;
                });
            }
        };
    }]);