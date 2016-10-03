'use strict';
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
                scope.rectangleContainedNodeIds = [];
                scope.disableContextMenu = false;
                scope.cntrlIsPressed = false;
                scope.mouseDown = false;
                scope.mouseButtonDown = 0;
                scope.rightClickEvent = null;
                scope.selectionRectangle = $("#selection-rectangle");
                scope.rectangleSelectionActive = false;
                scope.movingActive = false;
                scope.selectionRectanglePoints = {x1: 0, y1: 0, x2: 0, y2: 0, sx: 0, sy: 0, ex: 0, ey: 0};
                scope.selectionDisabled = false;
                scope.maximized = false;

                scope.updateSelectionRectangle = function () {
                    scope.selectionRectanglePoints.sx = Math.min(scope.selectionRectanglePoints.x1, scope.selectionRectanglePoints.x2);
                    scope.selectionRectanglePoints.ex = Math.max(scope.selectionRectanglePoints.x1, scope.selectionRectanglePoints.x2);
                    scope.selectionRectanglePoints.sy = Math.min(scope.selectionRectanglePoints.y1, scope.selectionRectanglePoints.y2);
                    scope.selectionRectanglePoints.ey = Math.max(scope.selectionRectanglePoints.y1, scope.selectionRectanglePoints.y2);
                    scope.selectionRectangle.css("left", scope.selectionRectanglePoints.sx + 'px');
                    scope.selectionRectangle.css("top", scope.selectionRectanglePoints.sy + 'px');
                    scope.selectionRectangle.css("width", scope.selectionRectanglePoints.ex - scope.selectionRectanglePoints.sx + 'px');
                    scope.selectionRectangle.css("height", scope.selectionRectanglePoints.ey - scope.selectionRectanglePoints.sy + 'px');
                };

                $.fn.flow = function () {
                    var lastPosition = null;
                    var position = null;

                    $($(this).selector).on("keydown", function (e) {
                        if (e.which == "17")
                            scope.cntrlIsPressed = true;
                    });

                    $($(this).selector).on("keyup", function (e) {
                        if (e.which == "17")
                            scope.cntrlIsPressed = false;
                    });

                    $($(this).selector).on("mousedown mouseup mousemove", function (e) {
                        if (e.button === 2)
                            scope.rightClickEvent = e;

                        if (e.type == "mousedown") {
                            scope.mouseButtonDown = e.button;
                            scope.mouseDown = true;
                            lastPosition = [e.clientX, e.clientY];
                            scope.disableContextMenu = false;

                            if (e.button === 2) {
                                scope.selectionRectanglePoints.x1 = (e.pageX - $("#flowContainer").offset().left) / scope.flowScale;
                                scope.selectionRectanglePoints.y1 = (e.pageY - $("#flowContainer").offset().top) / scope.flowScale;
                                scope.selectionRectanglePoints.x2 = scope.selectionRectanglePoints.x1;
                                scope.selectionRectanglePoints.y2 = scope.selectionRectanglePoints.y1;
                                scope.updateSelectionRectangle();
                            }
                        }
                        if (e.type == "mouseup") {
                            scope.movingActive = false;
                            if (scope.selectionDisabled) {
                                scope.selectionDisabled = false;
                                return;
                            }
                            scope.mouseDown = false;
                            if (e.button === 2) {
                                scope.rectangleContainedNodeIds = [];
                                scope.selectionRectangle.hide();
                                scope.rectangleSelectionActive = false;

                                var containedNodes = scope.getRectangleContainedNodeIds();
                                if (!scope.cntrlIsPressed && containedNodes.length > 0)
                                    scope.clearNodeSelection();
                                for (var i = 0; i < containedNodes.length; i++) {
                                    scope.addNodeToSelection(containedNodes[i]);
                                }
                            }
                            scope.$apply();
                        }

                        if (e.type == "mousemove" && scope.mouseDown == true && scope.mouseButtonDown === 2) {
                            scope.selectionRectanglePoints.x2 = (e.pageX - $("#flowContainer").offset().left) / scope.flowScale;
                            scope.selectionRectanglePoints.y2 = (e.pageY - $("#flowContainer").offset().top) / scope.flowScale;
                            scope.updateSelectionRectangle();
                            var difference = [scope.selectionRectanglePoints.x2 - scope.selectionRectanglePoints.x1, scope.selectionRectanglePoints.y2 - scope.selectionRectanglePoints.y1];
                            var dist = Math.sqrt(difference[0] * difference[0] + difference[1] * difference[1]);
                            if (dist > 2) {
                                scope.disableContextMenu = true;
                                scope.selectionRectangle.show();
                                scope.rectangleSelectionActive = true;
                                scope.rectangleContainedNodeIds = scope.getRectangleContainedNodeIds();
                                scope.$apply();
                            }
                        }

                        if (e.type == "mousemove" && scope.mouseDown == true && scope.mouseButtonDown === 0) {
                            scope.movingActive = true;
                            position = [e.clientX, e.clientY];
                            var difference = [(position[0] - lastPosition[0]), (position[1] - lastPosition[1])];
                            $(this).scrollLeft($(this).scrollLeft() - difference[0]);
                            $(this).scrollTop($(this).scrollTop() - difference[1]);
                            lastPosition = [e.clientX, e.clientY];
                            scope.$apply();
                        }
                    });
                };

                scope.toggleMaximize = function () {
                    scope.maximized = !scope.maximized;
                    if (scope.maximized) {
                        $("body").addClass("modal-open");
                    } else {
                        $("body").removeClass("modal-open");
                    }
                };

                scope.getRectangleContainedNodeIds = function () {
                    var result = [];
                    for (var i = 0; i < scope.object.nodes.length; i++) {
                        var node = scope.object.nodes[i];
                        var sx = scope.selectionRectanglePoints.sx;
                        var ex = scope.selectionRectanglePoints.ex;
                        var sy = scope.selectionRectanglePoints.sy;
                        var ey = scope.selectionRectanglePoints.ey;
                        if (node.posX >= sx && node.posX <= ex && node.posY >= sy && node.posY <= ey && node.type === 0)
                            result.push(node.id);
                    }
                    return result;
                };

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
                    var minZoom = 0.25;
                    var zoomSteps = 25;
                    var zoom = value > 0 ? scope.flowScale + (maxZoom - minZoom) / zoomSteps : scope.flowScale - (maxZoom - minZoom) / zoomSteps;
                    zoom = Math.max(minZoom, zoom);
                    zoom = Math.min(maxZoom, zoom);

                    var transformOrigin = [0, 0];
                    instance = instance || jsPlumb;
                    el = el || instance.getContainer();
                    var p = ["webkit", "moz", "ms", "o"],
                            s = "scale(" + zoom + ", " + zoom + ")",
                            oString = (transformOrigin[0] * 100) + "% " + (transformOrigin[1] * 100) + "%";
                    for (var i = 0; i < p.length; i++) {
                        el.style[p[i] + "Transform"] = s;
                        el.style[p[i] + "TransformOrigin"] = oString;
                    }

                    el.style["transform"] = s;
                    el.style["transformOrigin"] = oString;

                    var cw = $("#flowContainerWrapper");
                    cw.css("width", (zoom * 30000) + "px");
                    cw.css("height", (zoom * 30000) + "px");

                    instance.setZoom(zoom);

                    $("#flowContainerScroll").scrollLeft($("#flowContainerScroll")[0].scrollLeft * (zoom / scope.flowScale));
                    $("#flowContainerScroll").scrollTop($("#flowContainerScroll")[0].scrollTop * (zoom / scope.flowScale));


                    scope.flowScale = zoom;
                };

                scope.onKeyUp = function (event) {
                    if (!scope.cntrlIsPressed)
                        return;
                    if (scope.selectedNodeIds.length > 0 && event.which === 67) {
                        scope.copySelectedNodes();
                        return;
                    }
                    if (scope.copiedNodes.length > 0 && event.which === 86) {
                        scope.pasteNodes(scope.currentMouseEvent);
                        return;
                    }
                };

                scope.onFlowCtxOpened = function () {
                    scope.clearNodeSelection();
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

                scope.clearNodeSelection = function () {
                    scope.selectedNodeIds = [];
                    for (var i = 0; i < scope.object.nodes.length; i++) {
                        var node = scope.object.nodes[i];
                        node.selected = false;
                    }
                };

                scope.toggleNodeSelection = function (id, ignoreCtrl) {
                    if (scope.cntrlIsPressed || ignoreCtrl) {
                        var index = scope.selectedNodeIds.indexOf(id);
                        if (index === -1) {
                            scope.selectedNodeIds.push(id);
                            scope.collectionService.getNode(id).selected = true;
                        } else {
                            scope.selectedNodeIds.splice(index, 1);
                            scope.collectionService.getNode(id).selected = false;
                        }
                    }
                    $('#flowContainer').focus();
                };

                scope.addNodeToSelection = function (id) {
                    var index = scope.selectedNodeIds.indexOf(id);
                    if (index === -1) {
                        scope.selectedNodeIds.push(id);
                        scope.collectionService.getNode(id).selected = true;
                    }
                    $('#flowContainer').focus();
                };

                scope.isGetterNode = function (node) {
                    if (node.type !== 0)
                        return false;
                    for (var i = 0; i < node.ports.length; i++) {
                        var port = node.ports[i];
                        if (port.variableObject !== null && port.variableObject.type === 2)
                            return false;
                    }
                    return true;
                };

                scope.isNodeCollapsable = function (node) {
                    for (var i = 0; i < node.ports.length; i++) {
                        var port = node.ports[i];
                        if (port.variableObject && (port.variableObject.type === 0 || port.variableObject.type === 1))
                            return true;
                    }
                    return false;
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

                    var comment = "<div class='comment'><div class='comment-text'>" +
                            "<i class='glyphicon glyphicon-pencil clickable' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_FLOW_BUTTONS_COMMENT + "\"' ng-click='editNodeComment(collectionService.getNode(" + node.id + "))'></i> " +
                            "<span>{{collectionService.getNode(" + node.id + ").comment}}</span>" +
                            "</div></div>";
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
                            name = "<a ng-click='editNodeWizard(collectionService.getNode(" + node.id + "), collectionService.get(" + node.sourceTest + "))'>" + name + "</a>";
                        }
                    }

                    var elemHtml = "<div context-menu='onNodeCtxOpened(" + node.id + ")' data-target='menu-node' id='node" + node.id + "' class='node " + nodeClass + "' ng-class='{\"node-selected\": selectedNodeIds.indexOf(" + node.id + ")!==-1, \"node-selected-candidate\": rectangleContainedNodeIds.indexOf(" + node.id + ")!==-1}' style='top:" + node.posY + "px; left:" + node.posX + "px;' ng-click='toggleNodeSelection(" + node.id + ")'>";
                    var selectionCheckbox = "";
                    if (node.type === 1 || node.type === 2) {
                        elemHtml = "<div id='node" + node.id + "' class='node " + nodeClass + "' style='top:" + node.posY + "px; left:" + node.posX + "px;'>";
                    } else {
                        selectionCheckbox = "<div class='node-selection-checkbox'><input type='checkbox' ng-model='collectionService.getNode(" + node.id + ").selected' ng-change='toggleNodeSelection(" + node.id + ", true)' /></div>";
                    }
                    var collapseHtml = "";
                    if (scope.isNodeCollapsable(node)) {
                        collapseHtml = "<div style='display: table; margin: auto;'>" +
                                "<i class='glyphicon clickable' ng-class='{\"glyphicon-arrow-up\": collectionService.getNode(" + node.id + ").expanded, \"glyphicon-arrow-down\": !collectionService.getNode(" + node.id + ").expanded}' " +
                                "ng-click='toggleUnconnectedPortsCollapse(" + node.id + ")' " +
                                "tooltip-placement='bottom' tooltip-append-to-body='false' uib-tooltip='" + Trans.TEST_FLOW_BUTTONS_TOGGLE_COLLAPSE_TOOLTIP + "'></i></div>" +
                                "</div>";
                    }
                    elemHtml += comment +
                            "<div class='nodeHeader' tooltip-append-to-body='true' uib-tooltip-html='\"" + fullName + "\"'>" + selectionCheckbox + tooltip + name + "</div>" +
                            "<div class='nodeFooter'>" +
                            collapseHtml +
                            "</div>";
                    var elem = $(elemHtml).appendTo("#flowContainer");
                    var leftCount = 0;
                    var rightCount = 0;
                    //in port
                    if (node.type !== 1 && !scope.isGetterNode(node)) {
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
                                            var tooltip = Trans.TEST_FLOW_PORT_DESCRIPTION_IN;
                                            var overlayElem = $("<div>" +
                                                    "<div class='portLabel portLabelIn' uib-tooltip-html='\"" + tooltip + "\"' tooltip-append-to-body='true'>" + Trans.TEST_FLOW_PORT_NAME_IN + "</div>" +
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
                                                    var overlayElem = $("<div>" +
                                                            "<div class='portLabel portLabelBranch' uib-tooltip-html='getPortTooltip(" + portId + ")' tooltip-append-to-body='true'>" + varName + "</div>" +
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
                                                var overlayElem = $("<div>" +
                                                        "<div " +
                                                        "ng-class='{\"portLabel\": true, \"portLabelInput\": true, \"portLabelInputString\": collectionService.getPort(" + portId + ").string === \"1\"}' " +
                                                        "uib-tooltip-html='getPortTooltip(" + portId + ")' tooltip-append-to-body='true'>" + varName + "</div>" +
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
                                                var overlayElem = $("<div>" +
                                                        "<div class='portLabel portLabelReturn' uib-tooltip-html='getPortTooltip(" + portId + ")' tooltip-append-to-body='true'>" + varName + "</div>" +
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
                        drag: function (event, ui) {
                            scope.movingActive = true;
                            scope.selectionDisabled = true;
                            scope.$apply();
                            if (scope.selectedNodeIds.indexOf(node.id) === -1)
                                return;
                            var offset = {
                                x: elem.position().left / scope.flowScale - node.posX,
                                y: elem.position().top / scope.flowScale - node.posY
                            };

                            node.posX = elem.position().left / scope.flowScale;
                            node.posY = elem.position().top / scope.flowScale;

                            for (var a = 0; a < scope.selectedNodeIds.length; a++) {
                                var id = scope.selectedNodeIds[a];
                                if (id == node.id)
                                    continue;
                                for (var i = 0; i < scope.object.nodes.length; i++) {
                                    var n = scope.object.nodes[i];
                                    if (n.id === id) {
                                        n.posX += offset.x;
                                        n.posY += offset.y;
                                        var nelem = $("#node" + n.id);
                                        nelem.css("top", n.posY + "px");
                                        nelem.css("left", n.posX + "px");
                                        jsPlumb.revalidate(nelem);
                                    }
                                }
                            }
                        },
                        stop: function (event, ui) {
                            scope.movingActive = false;
                            scope.$apply();
                            if (scope.selectedNodeIds.indexOf(node.id) === -1) {
                                var x = elem.position().left / scope.flowScale;
                                var y = elem.position().top / scope.flowScale;
                                $http.post(Paths.TEST_FLOW_NODE_SAVE.pf(node.id), {
                                    "type": node.type,
                                    "flowTest": scope.object.id,
                                    "sourceTest": node.sourceTest,
                                    "posX": x,
                                    "posY": y,
                                    "comment": node.comment
                                }).success(function (data) {
                                    if (data.result === 0) {
                                        node.posX = x;
                                        node.posY = y;
                                    }
                                });
                            } else {
                                $http.post(Paths.TEST_FLOW_NODE_MOVE, {
                                    nodes: scope.serializeSelectedNodes()
                                }).success(function (data) {

                                });
                            }
                        }
                    });
                    $compile(elem)(scope);
                };

                scope.getPortTooltip = function (portId) {
                    var port = scope.collectionService.getPort(portId);
                    var varName = port.variableObject.name;
                    var description = port.variableObject.description;
                    var tooltip = "<b>" + varName + "</b>";
                    if (description && description != "")
                        tooltip += "<br/><br/>" + port.variableObject.description;
                    return tooltip;
                }

                scope.serializeSelectedNodes = function () {
                    var result = [];
                    for (var i = 0; i < scope.selectedNodeIds.length; i++) {
                        var id = scope.selectedNodeIds[i];
                        for (var j = 0; j < scope.object.nodes.length; j++) {
                            var node = scope.object.nodes[j];
                            if (id != node.id)
                                continue;
                            result.push({
                                id: node.id,
                                posX: node.posX,
                                posY: node.posY
                            });
                        }
                    }
                    return angular.toJson(result);
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

                scope.editNodeComment = function (node) {
                    var oldComment = node.comment;
                    var modalInstance = $uibModal.open({
                        templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "textarea_dialog.html",
                        controller: TextareaController,
                        resolve: {
                            readonly: function () {
                                return false;
                            },
                            value: function () {
                                return node.comment;
                            },
                            title: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_EDIT_COMMENT_TITLE;
                            },
                            tooltip: function () {
                                return Trans.TEST_FLOW_DIALOG_NODE_EDIT_COMMENT_TOOLTIP;
                            }
                        },
                        size: "lg"
                    });

                    modalInstance.result.then(function (response) {
                        $http.post(Paths.TEST_FLOW_NODE_SAVE.pf(node.id), {
                            "type": node.type,
                            "flowTest": scope.object.id,
                            "sourceTest": node.sourceTest,
                            "posX": node.posX,
                            "posY": node.posY,
                            "comment": response
                        }).success(function (data) {
                            node.comment = data.object.comment;
                        });
                    }, function () {
                        node.comment = oldComment;
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
                    var posX = (scope.rightClickEvent.offsetX || (scope.rightClickEvent.pageX - $(scope.rightClickEvent.target).offset().left) / scope.flowScale);
                    var posY = (scope.rightClickEvent.offsetY || (scope.rightClickEvent.pageY - $(scope.rightClickEvent.target).offset().top) / scope.flowScale);
                    if (testId == null)
                        testId = scope.object.id;
                    $http.post(Paths.TEST_FLOW_NODE_ADD_COLLECTION.pf(scope.object.id), {
                        "type": type,
                        "flowTest": scope.object.id,
                        "sourceTest": testId,
                        "posX": posX,
                        "posY": posY,
                        "comment": ""
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

                scope.copyNode = function (id) {
                    for (var i = 0; i < scope.object.nodes.length; i++) {
                        var node = scope.object.nodes[i];
                        if (node.id === id) {
                            scope.copiedNodes = [
                                node
                            ];
                            break;
                        }
                    }
                };

                scope.copySelectedNodes = function () {
                    var nodes = [];
                    for (var j = 0; j < scope.selectedNodeIds.length; j++) {
                        var id = scope.selectedNodeIds[j];
                        for (var i = 0; i < scope.object.nodes.length; i++) {
                            var node = scope.object.nodes[i];
                            if (node.id === id) {
                                nodes.push(node);
                                break;
                            }
                        }
                    }
                    scope.copiedNodes = nodes;
                };

                scope.pasteNodes = function (cursorPos) {
                    var posX = 0;
                    var posY = 0;
                    if (!cursorPos) {
                        posX = (scope.rightClickEvent.offsetX || (scope.rightClickEvent.pageX - $(scope.rightClickEvent.target).offset().left) / scope.flowScale);
                        posY = (scope.rightClickEvent.offsetY || (scope.rightClickEvent.pageY - $(scope.rightClickEvent.target).offset().top) / scope.flowScale);
                    } else {
                        posX = (scope.currentMouseEvent.offsetX || (scope.currentMouseEvent.pageX - $(scope.currentMouseEvent.target).offset().left) / scope.flowScale);
                        posY = (scope.currentMouseEvent.offsetY || (scope.currentMouseEvent.pageY - $(scope.currentMouseEvent.target).offset().top) / scope.flowScale);
                    }
                    var offset = null;

                    var nodes = angular.copy(scope.copiedNodes);

                    for (var i = 0; i < nodes.length; i++) {
                        var node = nodes[i];
                        if (offset === null) {
                            offset = {
                                posX: node.posX,
                                posY: node.posY
                            };
                        } else {
                            offset.posX = Math.min(offset.posX, node.posX);
                            offset.posY = Math.min(offset.posY, node.posY);
                        }
                    }

                    for (var i = 0; i < nodes.length; i++) {
                        var node = nodes[i];
                        node.posX = posX + node.posX - offset.posX;
                        node.posY = posY + node.posY - offset.posY;
                    }

                    var serializedNodes = angular.toJson(nodes);
                    $http.post(Paths.TEST_FLOW_NODE_PASTE_COLLECTION.pf(scope.object.id), {
                        nodes: serializedNodes
                    }).success(function (data) {
                        scope.object.nodes = data.collections.nodes;
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
                        for (var a = 0; a < scope.selectedNodeIds.length; a++) {
                            var id = scope.selectedNodeIds[a];
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
                                for (var a = 0; a < scope.selectedNodeIds.length; a++) {
                                    var id = scope.selectedNodeIds[a];
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
                            //scope.setUpConnection(jspConnection);
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
                        if (jspConnection.getOverlay("overlayConnection" + params.concertoConnection.id))
                            return;
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
                            $("#overlayConnection" + id).remove();
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
                    scope.clearNodeSelection();
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
                    $("#flowContainerScroll").flow();
                    $('#flowContainer').mousewheel(function (event) {
                        scope.setZoom(event.deltaY);
                        return false;
                    }).mousemove(function (event) {
                        scope.currentMouseEvent = event;
                    }).keyup(function (event) {
                        scope.onKeyUp(event);
                    }).focus();
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

                scope.$on('$locationChangeStart', function (event, toUrl, fromUrl) {
                    if (scope.maximized)
                        scope.toggleMaximize();
                });
            }
        };
    }]);