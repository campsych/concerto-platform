'use strict';
angular.module('concertoPanel').directive('flowLogic', ['$http', '$compile', '$timeout', '$uibModal', '$filter', 'TestCollectionService', 'DialogsService', function ($http, $compile, $timeout, $uibModal, $filter, TestCollectionService, DialogsService) {
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
      scope.mouseDown = false;
      scope.mouseButtonDown = 0;
      scope.rightClickEvent = null;
      scope.selectionRectangle = $("#selection-rectangle");
      scope.rectangleSelectionActive = false;
      scope.movingActive = false;
      scope.selectionRectanglePoints = {x1: 0, y1: 0, x2: 0, y2: 0, sx: 0, sy: 0, ex: 0, ey: 0};
      scope.selectionDisabled = false;
      scope.maximized = false;
      scope.lastActiveNodeId = null;
      scope.jsPlumbEventsEnabled = true;
      scope.dialogsService = DialogsService;

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
              if (containedNodes.length > 0)
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
            }
          }

          if (e.type == "mousemove" && scope.mouseDown == true && scope.mouseButtonDown === 0) {
            scope.movingActive = true;
            position = [e.clientX, e.clientY];
            var difference = [(position[0] - lastPosition[0]), (position[1] - lastPosition[1])];
            $(this).scrollLeft($(this).scrollLeft() - difference[0]);
            $(this).scrollTop($(this).scrollTop() - difference[1]);
            lastPosition = [e.clientX, e.clientY];
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
          if (node.posX >= sx && node.posX <= ex && node.posY >= sy && node.posY <= ey && node.type == 0)
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

      scope.onFlowCtxOpened = function () {
        scope.clearNodeSelection();
      };

      scope.onNodeCtxOpened = function ($event, nodeId) {
        if (scope.selectedNodeIds.indexOf(nodeId) === -1) {
          scope.clearNodeSelection();
        }

        scope.setLastActiveNodeId(nodeId);
        for (var i = 0; i < scope.object.nodes.length; i++) {
          var node = scope.object.nodes[i];
          if (nodeId === node.id)
            scope.nodeContext = node;
        }
      };

      scope.truncateNodeTitle = function (title) {
        return title;
      };

      scope.exportTest = function (nodeId) {
        var nodeIds = nodeId;
        if (scope.selectedNodeIds.length > 0)
          nodeIds = scope.selectedNodeIds.join(",");
        var modalInstance = $uibModal.open({
          templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'export_dialog.html',
          controller: ExportController,
          size: "lg",
          resolve: {
            title: function () {
              return Trans.EXPORT_DIALOG_TITLE;
            },
            content: function () {
              return Trans.EXPORT_DIALOG_EMPTY_LIST_ERROR_CONTENT;
            },
            ids: function () {
              return nodeIds;
            }
          }
        });
        modalInstance.result.then(function (response) {
          window.open(Paths.TEST_FLOW_NODE_EXPORT.pf(nodeIds) + "/" + response, "_blank");
        });
      };

      scope.clearNodeSelection = function () {
        scope.selectedNodeIds = [];
        for (var i = 0; i < scope.object.nodes.length; i++) {
          var node = scope.object.nodes[i];
          node.selected = false;
        }
      };

      scope.toggleNodeSelection = function (id) {
        var index = scope.selectedNodeIds.indexOf(id);
        if (index === -1) {
          scope.selectedNodeIds.push(id);
          scope.collectionService.getNode(id).selected = true;
        } else {
          scope.selectedNodeIds.splice(index, 1);
          scope.collectionService.getNode(id).selected = false;
        }
      };

      scope.addNodeToSelection = function (id) {
        var index = scope.selectedNodeIds.indexOf(id);
        if (index === -1) {
          scope.selectedNodeIds.push(id);
          scope.collectionService.getNode(id).selected = true;
        }
      };

      scope.isGetterNode = function (node) {
        if (node.type != 0)
          return false;
        for (var i = 0; i < node.ports.length; i++) {
          var port = node.ports[i];
          if (port.variableObject !== null && port.variableObject.type == 2)
            return false;
        }
        return true;
      };

      scope.isNodeCollapsable = function (node) {
        for (var i = 0; i < node.ports.length; i++) {
          var port = node.ports[i];
          if (port.variableObject && (port.variableObject.type == 0 || port.variableObject.type == 1))
            return true;
        }
        return false;
      };

      scope.refreshNode = function (node) {
        scope.jsPlumbEventsEnabled = false;
        jsPlumb.setSuspendDrawing(true);
        jsPlumb.remove("node" + node.id);
        scope.drawNode(node);
        scope.jsPlumbEventsEnabled = true;
        for (var i = 0; i < scope.object.nodesConnections.length; i++) {
          var connection = scope.object.nodesConnections[i];
          if (connection.sourceNode == node.id || connection.destinationNode == node.id) {
            scope.connect(connection);
          }
        }
        jsPlumb.setSuspendDrawing(false, true);
      }

      scope.refreshConnections = function (nodesIds, manualDrawingResume) {
        scope.jsPlumbEventsEnabled = false;
        jsPlumb.setSuspendDrawing(true);
        for (var i = 0; i < scope.object.nodes.length; i++) {
          var node = scope.object.nodes[i];
          if (nodesIds.indexOf(node.id) !== -1) {
            jsPlumb.remove("node" + node.id);
            scope.drawNode(node);
          }
        }
        scope.jsPlumbEventsEnabled = true;
        for (var i = 0; i < scope.object.nodesConnections.length; i++) {
          var connection = scope.object.nodesConnections[i];
          if (nodesIds.indexOf(connection.sourceNode) !== -1 || nodesIds.indexOf(connection.destinationNode) !== -1) {
            scope.connect(connection);
          }
        }
        if (!manualDrawingResume)
          jsPlumb.setSuspendDrawing(false, true);
      }

      scope.drawNode = function (node) {

        /* SETTINGS START */
        var portTopMargin = 20;
        var portElemMargin = 30;
        var portBottomMargin = -10;
        var flowEndpoint = ["Rectangle", {width: 25, height: 25}];
        var varEndpoint = ["Dot", {radius: 12.5}];
        /* SETTINGS END */

        node.ports = $filter('orderBy')(node.ports, "variableObject.name");
        var fullName = "";
        var title = "";
        var nodeClass = "";
        var description = scope.collectionService.getNode(node.id).sourceTestDescription;
        if (node.type == 1) {
          fullName = Trans.TEST_FLOW_NODE_NAME_START;
          if (node.title != "")
            title = scope.truncateNodeTitle(node.title);
          else
            title = scope.truncateNodeTitle(fullName);
          description = Trans.TEST_FLOW_NODE_DESCRIPTION_START;
          nodeClass = "nodeStart";
        } else if (node.type == 2) {
          fullName = Trans.TEST_FLOW_NODE_NAME_END;
          if (node.title != "")
            title = scope.truncateNodeTitle(node.title);
          else
            title = scope.truncateNodeTitle(fullName);
          description = Trans.TEST_FLOW_NODE_DESCRIPTION_END;
          nodeClass = "nodeEnd";
        } else if (node.type == 0) {
          fullName = node.sourceTestName;
          if (node.title != "")
            title = scope.truncateNodeTitle(node.title);
          else
            title = scope.truncateNodeTitle(fullName);
          var test = scope.collectionService.get(node.sourceTest);
          if (test.sourceWizard) {
            title = "<a ng-click='editNodeWizard(collectionService.getNode(" + node.id + "), collectionService.get(" + node.sourceTest + "))'>" + title + "</a>";
          }
        }

        var elemHtml = "<div context-menu='onNodeCtxOpened($event, " + node.id + ")' data-target='menu-node' id='node" + node.id + "' class='node " + nodeClass + "' ng-class='{\"node-selected\": selectedNodeIds.indexOf(" + node.id + ")!==-1, \"node-selected-candidate\": rectangleContainedNodeIds.indexOf(" + node.id + ")!==-1, \"node-expanded\": collectionService.getNode(" + node.id + ").expanded, \"node-active\": " + node.id + "===lastActiveNodeId}' style='top:" + node.posY + "px; left:" + node.posX + "px;' ng-click='setLastActiveNodeId(" + node.id + ");' context-menu-disabled='object.starterContent && !administrationSettingsService.starterContentEditable'>";
        var headerIcons = "";
        if (node.type == 1 || node.type == 2) {
          elemHtml = "<div id='node" + node.id + "' class='node " + nodeClass + "' style='top:" + node.posY + "px; left:" + node.posX + "px;' ng-class='{\"node-expanded\": collectionService.getNode(" + node.id + ").expanded, \"node-active\": " + node.id + "===lastActiveNodeId }' ng-click='setLastActiveNodeId(" + node.id + ")'>";
        } else {
          headerIcons = "<div class='node-header-icons'>" +
              "<i class='clickable glyphicon glyphicon-menu-hamburger' tooltip-append-to-body='true' uib-tooltip-html='\"" + Trans.TEST_FLOW_BUTTONS_NODE_MENU + "\"' ng-click='openNodeContextMenu($event, " + node.id + ")'></i>" +
              "<input type='checkbox' ng-model='collectionService.getNode(" + node.id + ").selected' ng-change='toggleNodeSelection(" + node.id + ")' />" +
              "</div>";
        }
        var collapseHtml = "";
        if (scope.isNodeCollapsable(node)) {
          collapseHtml = "<div style='width: 100%; text-align: center;'><button class='btn btn-default btn-xs btn-block' ng-click='toggleUnconnectedPortsCollapse(" + node.id + ")'>" +
              "<i class='glyphicon' ng-class='{\"glyphicon-arrow-up\": collectionService.getNode(" + node.id + ").expanded, \"glyphicon-arrow-down\": !collectionService.getNode(" + node.id + ").expanded}' " +
              "></i></button></div>" +
              "</div>";
        }
        elemHtml += "<div class='node-header' tooltip-append-to-body='true' uib-tooltip-html='\"" + description + "\"'>" + headerIcons + title + "</div>" +
            "<div class='node-content'><div class='node-content-left'></div><div class='node-content-right'></div></div>" +
            "<div class='node-footer'>" +
            collapseHtml +
            "</div>";
        var elem = $(elemHtml).appendTo("#flowContainer");
        var elemContent = elem.find(".node-content");
        var elemContentLeft = elemContent.find(".node-content-left");
        var elemContentRight = elemContent.find(".node-content-right");
        var leftCount = 0;
        var rightCount = 0;
        //in port
        if (node.type != 1 && !scope.isGetterNode(node)) {
          var tooltip = Trans.TEST_FLOW_PORT_DESCRIPTION_IN;
          var overlayElem = $("<div class='portLabel portLabelIn' uib-tooltip-html='\"" + tooltip + "\"' tooltip-append-to-body='true'>" + Trans.TEST_FLOW_PORT_NAME_IN + "</div>");
          $compile(overlayElem)(scope);
          overlayElem.appendTo(elemContentLeft);

          jsPlumb.addEndpoint(elemContent, {
            uuid: "node" + node.id + "-ep_entry",
            isTarget: true,
            maxConnections: -1,
            endpoint: flowEndpoint,
            anchor: [-0.042, 0, -1, 0, 0, portTopMargin + leftCount * portElemMargin],
            paintStyle: {fillStyle: "white", strokeStyle: "grey"},
            parameters: {
              targetNode: node,
              targetPort: null
            }
          }).setEnabled(!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable);
          leftCount++;
        }

        if (node.type != 2) {
          for (var i = 0; i < node.ports.length; i++) {
            var port = node.ports[i];
            if (!port.variable) continue;

            if (port.variableObject.type == 2) { //branches

              var overlayElem = $("<div class='portLabel portLabelBranch' uib-tooltip-html='getPortTooltip(" + port.id + ")' tooltip-append-to-body='true'>" + port.variableObject.name + "</div>");
              $compile(overlayElem)(scope);
              overlayElem.appendTo(elemContentRight);

              jsPlumb.addEndpoint(elemContent, {
                uuid: "node" + node.id + "-ep" + port.id,
                isSource: true,
                maxConnections: 1,
                endpoint: flowEndpoint,
                anchor: [1.053, 0, 1, 0, 0, portTopMargin + rightCount * portElemMargin],
                paintStyle: {fillStyle: "orange", strokeStyle: "grey"},
                parameters: {
                  sourceNode: node,
                  sourcePort: port
                }
              }).setEnabled(!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable);
              rightCount++;
            }
          }

          if (node.type == 1) {
            var overlayElem = $("<div class='portLabel portLabelBranch' uib-tooltip-html='\"" + Trans.TEST_FLOW_PORT_DESCRIPTION_OUT + "\"' tooltip-append-to-body='true'>" + Trans.TEST_FLOW_PORT_NAME_OUT + "</div>");
            $compile(overlayElem)(scope);
            overlayElem.appendTo(elemContentRight);

            jsPlumb.addEndpoint(elemContent, {
              uuid: "node" + node.id + "-ep_out",
              isSource: true,
              maxConnections: 1,
              endpoint: flowEndpoint,
              anchor: [1.053, 0, 1, 0, 0, portTopMargin + rightCount * portElemMargin],
              paintStyle: {fillStyle: "orange", strokeStyle: "grey"},
              parameters: {
                sourceNode: node,
                sourcePort: null
              }
            }).setEnabled(!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable);
            rightCount++;
          }
        }

        for (var i = 0; i < node.ports.length; i++) {
          var port = node.ports[i];

          if (scope.isPortVisible(node, port) && ((node.type == 0 && port.variableObject.type == 0) || (node.type == 2 && port.variableObject.type == 1))) { //input param

            var overlayElem = $("<div " +
                "ng-class='{\"portLabel\": true, \"portLabelInput\": true, \"portLabelInputString\": collectionService.getPort(" + port.id + ").string === \"1\", \"portLabelInputR\": collectionService.getPort(" + port.id + ").string === \"0\"}' " +
                "uib-tooltip-html='getPortTooltip(" + port.id + ")' tooltip-append-to-body='true'>" + port.variableObject.name + "</div>");
            $compile(overlayElem)(scope);
            overlayElem.appendTo(elemContentLeft);

            jsPlumb.addEndpoint(elemContent, {
              uuid: "node" + node.id + "-ep" + port.id,
              maxConnections: -1,
              isTarget: true,
              endpoint: varEndpoint,
              anchor: [-0.042, 0, -1, 0, 0, portTopMargin + leftCount * portElemMargin],
              paintStyle: {fillStyle: "blue", strokeStyle: "grey"},
              overlays: [[
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
                        "<i ng-class='{\"glyphInteractable\": true, \"glyphicon\": true, \"glyphicon-align-justify\": true, \"port-value-default\": collectionService.getPort(" + portId + ").defaultValue == \"1\"}' " +
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
            }).setEnabled(!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable);
            leftCount++;
          } else if (scope.isPortVisible(node, port) && ((node.type == 0 && port.variableObject.type == 1) || (node.type == 1 && port.variableObject.type == 0))) { //return vars

            var overlayElem = $("<div>" +
                "<div class='portLabel portLabelReturn' uib-tooltip-html='getPortTooltip(" + port.id + ")' tooltip-append-to-body='true'>" + port.variableObject.name + "</div>" +
                "</div>");
            $compile(overlayElem)(scope);
            overlayElem.appendTo(elemContentRight);

            jsPlumb.addEndpoint(elemContent, {
              uuid: "node" + node.id + "-ep" + port.id,
              isSource: true,
              maxConnections: -1,
              endpoint: varEndpoint,
              anchor: [1.053, 0, 1, 0, 0, portTopMargin + rightCount * portElemMargin],
              paintStyle: {fillStyle: "red", strokeStyle: "grey"},
              parameters: {
                sourceNode: node,
                sourcePort: port
              }
            }).setEnabled(!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable);
            rightCount++;
          }
        }

        elemContent.css("height", (portTopMargin + Math.max(leftCount, rightCount) * portElemMargin + portBottomMargin) + "px");
        if (!scope.object.starterContent || scope.administrationSettingsService.starterContentEditable) {
          jsPlumb.draggable(elem, {
            containment: true,
            drag: function (event, ui) {
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
            start: function (event, ui) {
              scope.movingActive = true;
              scope.selectionDisabled = true;
              scope.setLastActiveNodeId(node.id);
            },
            stop: function (event, ui) {
              scope.movingActive = false;
              if (scope.selectedNodeIds.indexOf(node.id) === -1) {
                var x = elem.position().left / scope.flowScale;
                var y = elem.position().top / scope.flowScale;
                $http.post(Paths.TEST_FLOW_NODE_SAVE.pf(node.id), {
                  "type": node.type,
                  "flowTest": scope.object.id,
                  "sourceTest": node.sourceTest,
                  "posX": x,
                  "posY": y,
                  "title": node.title
                }).success(function (data) {
                  if (data.result === 0) {
                    node.posX = x;
                    node.posY = y;
                  }
                });
              } else {
                $http.post(Paths.TEST_FLOW_NODE_MOVE, {
                  nodes: scope.serializeSelectedNodes()
                });
              }
            }
          });
        }
        $compile(elem)(scope);
      };

      scope.openNodeContextMenu = function ($event, id) {
        $timeout(function () {
          var elem = angular.element('#node' + id);
          elem.trigger({type: "contextmenu", pageX: $event.pageX, pageY: $event.pageY});
        });
      };

      scope.setLastActiveNodeId = function (id) {
        scope.lastActiveNodeId = id;
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
              copiedTest.starterContent = scope.object.starterContent;
              return copiedTest;
            }
          },
          size: "prc-lg"
        });

        modalInstance.result.then(function (response) {
          $http.post(Paths.TEST_FLOW_PORT_SAVE_COLLECTION, {
            "serializedCollection": angular.toJson(response.ports)
          });
        }, function () {
          scope.collectionService.updateNode(oldValue);
        });
      };

      scope.editNodeTitle = function (node) {
        var oldTitle = node.title;
        var modalInstance = $uibModal.open({
          templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "textarea_dialog.html",
          controller: TextareaController,
          resolve: {
            readonly: function () {
              return false;
            },
            value: function () {
              return node.title;
            },
            title: function () {
              return Trans.TEST_FLOW_DIALOG_NODE_EDIT_TITLE_TITLE;
            },
            tooltip: function () {
              return Trans.TEST_FLOW_DIALOG_NODE_EDIT_TITLE_TOOLTIP;
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
            "title": response
          }).success(function (data) {
            node.title = data.object.title;
            scope.refreshNode(node);
          });
        }, function () {
          node.title = oldTitle;
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
            },
            editable: function () {
              return !scope.object.starterContent || scope.administrationSettingsService.starterContentEditable;
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
          templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "connection_return_function_dialog.html",
          controller: ConnectionReturnFunctionController,
          scope: scope,
          resolve: {
            object: function () {
              return connection;
            },
            title: function () {
              return connection.sourcePortObject.variableObject.name + "->" + connection.destinationPortObject.variableObject.name;
            },
            editable: function () {
              return !scope.object.starterContent || scope.administrationSettingsService.starterContentEditable;
            }
          },
          size: "lg"
        });

        modalInstance.result.then(function (object) {
          $http.post(Paths.TEST_FLOW_CONNECTION_SAVE.pf(connection.id), {
            "flowTest": object.flowTest,
            "sourceNode": object.sourceNode,
            "sourcePort": object.sourcePort,
            "destinationNode": object.destinationNode,
            "destinationPort": object.destinationPort,
            "returnFunction": object.returnFunction,
            "default": object.defaultReturnFunction
          }).success(function (data) {
            connection.returnFunction = data.object.returnFunction
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
          "title": ""
        }).success(function (data) {
          if (data.result === 0) {
            scope.object.nodes.push(data.object);
            scope.drawNode(data.object);

            var sourceTest = angular.copy(TestCollectionService.get(data.object.sourceTest));

            if (sourceTest && sourceTest.sourceWizard) {
              scope.editNodeWizard(data.object, sourceTest);
            }
          }
        });
      };

      scope.copyNode = function (id) {
        if (scope.selectedNodeIds.length > 0) {
          scope.copySelectedNodes();
          return;
        }

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
        if (scope.object.starterContent && !administrationSettingsService.starterContentEditable)
          return false;

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
          var nodeIds = [];
          for (var i = 0; i < data.collections.newNodes.length; i++) {
            var node = data.collections.newNodes[i];
            scope.object.nodes.push(node);
            nodeIds.push(node.id);
          }
          for (var i = 0; i < data.collections.newNodesConnections.length; i++) {
            var connection = data.collections.newNodesConnections[i];
            scope.object.nodesConnections.push(connection);
          }
          scope.refreshConnections(nodeIds);
        });
      };

      scope.removeNode = function (id) {
        if (scope.selectedNodeIds.length > 0) {
          scope.removeSelectedNodes();
          return;
        }

        scope.dialogsService.confirmDialog(
            Trans.TEST_FLOW_DIALOG_NODE_REMOVE_TITLE,
            Trans.TEST_FLOW_DIALOG_NODE_REMOVE_MESSAGE,
            function (response) {
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

              $http.post(Paths.TEST_FLOW_NODE_DELETE_COLLECTION.pf(id), {}).success(function (data) {
                if (data.result === 0) {
                  jsPlumb.remove("node" + id);
                  for (var i = scope.object.nodes.length - 1; i >= 0; i--) {
                    var node = scope.object.nodes[i];
                    if (node.id == id) {
                      scope.object.nodes.splice(i, 1);
                      break;
                    }
                  }
                  for (var i = scope.object.nodesConnections.length - 1; i >= 0; i--) {
                    var connection = scope.object.nodesConnections[i];
                    if (connection.sourceNode == id || connection.destinationNode == id) {
                      scope.object.nodesConnections.splice(i, 1);
                    }
                  }
                }
              });
            }
        );
      };

      scope.removeSelectedNodes = function () {
        scope.dialogsService.confirmDialog(
            Trans.TEST_FLOW_DIALOG_NODE_REMOVE_SELECTION_TITLE,
            Trans.TEST_FLOW_DIALOG_NODE_REMOVE_SELECTION_MESSAGE,
            function (response) {
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

              $http.post(Paths.TEST_FLOW_NODE_DELETE_COLLECTION.pf(scope.selectedNodeIds.join()), {}).success(function (data) {
                if (data.result === 0) {
                  for (var a = 0; a < scope.selectedNodeIds.length; a++) {
                    var id = scope.selectedNodeIds[a];
                    jsPlumb.remove("node" + id);

                    for (var i = scope.object.nodes.length - 1; i >= 0; i--) {
                      var node = scope.object.nodes[i];
                      if (node.id == id) {
                        scope.object.nodes.splice(i, 1);
                        break;
                      }
                    }
                    for (var i = scope.object.nodesConnections.length - 1; i >= 0; i--) {
                      var connection = scope.object.nodesConnections[i];
                      if (connection.sourceNode == id || connection.destinationNode == id) {
                        scope.object.nodesConnections.splice(i, 1);
                      }
                    }
                  }
                }
              });
            }
        );
      };

      scope.addConnection = function (concertoConnection, jspConnection) {
        var params = jspConnection.getParameters();
        $http.post(Paths.TEST_FLOW_CONNECTION_ADD_COLLECTION.pf(scope.object.id), {
          "flowTest": scope.object.id,
          "sourceNode": params.sourceNode.id,
          "sourcePort": params.sourcePort ? params.sourcePort.id : null,
          "destinationNode": params.targetNode.id,
          "destinationPort": params.targetPort ? params.targetPort.id : null,
          "default": "1"
        }).success(function (data) {
          if (data.result === 0) {
            for (var i = 0; i < data.collections.newNodesConnections.length; i++) {
              var newConnection = data.collections.newNodesConnections[i];
              var found = false;
              for (var j = 0; j < scope.object.nodesConnections.length; j++) {
                var connection = scope.object.nodesConnections[j];
                if (connection.id == newConnection.id) {
                  found = true;
                  break;
                }
              }
              if (!found) {
                scope.object.nodesConnections.push(newConnection);
              }
            }
            scope.refreshConnections([params.sourceNode.id, params.targetNode.id]);
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
          "destinationPort": params.targetPort ? params.targetPort.id : null,
          "default": "1"
        }).success(function (data) {
          if (data.result === 0) {
            jspConnection.setParameter("concertoConnection", data.object);
            for (var j = 0; j < scope.object.nodesConnections.length; j++) {
              var connection = scope.object.nodesConnections[j];
              if (connection.id == data.object.id) {
                scope.object.nodesConnections[j] = data.object;

                for (var k = 0; k < scope.object.nodes.length; k++) {
                  var node = scope.object.nodes[k];
                  if (node.id == connection.destinationNode) {
                    scope.refreshNode(node);
                    break;
                  }
                }
                break;
              }
            }
          }
        });
      };

      scope.connect = function (concertoConnection) {
        jsPlumb.connect({
          uuids: [
            "node" + concertoConnection.sourceNode + "-ep" + (concertoConnection.sourcePort ? concertoConnection.sourcePort : "_out"),
            "node" + concertoConnection.destinationNode + "-ep" + (concertoConnection.destinationPort ? concertoConnection.destinationPort : "_entry"),
          ],
          parameters: {
            concertoConnection: concertoConnection
          },
          paintStyle: {
            dashstyle: "dot",
            strokeStyle: scope.getConnectionStrokeStyle(concertoConnection.automatic, concertoConnection.sourcePortObject ? concertoConnection.sourcePortObject.variableObject.type : 2),
            lineWidth: scope.getConnectionLineWidth(concertoConnection.sourcePortObject ? concertoConnection.sourcePortObject.variableObject.type : 2)
          }
        });
      };

      scope.getConnectionStrokeStyle = function (automatic, type) {
        switch (parseInt(type)) {
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
        switch (parseInt(type)) {
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
        if ((params.sourceNode.type == 1 && params.sourcePort && params.sourcePort.variableObject.type == 0) || (params.sourceNode.type == 0 && params.sourcePort.variableObject.type == 1)) {
          if (jspConnection.getOverlay("overlayConnection" + params.concertoConnection.id))
            return;
          jspConnection.addOverlay(
              ["Custom", {
                create: function (component) {
                  var overlayElem = $("<div>" +
                      "<div id='divConnectionControl" + params.concertoConnection.id + "'>" +
                      "<i class='glyphInteractable glyphicon glyphicon-align-justify' ng-class='{\"return-function-default\": collectionService.getConnection(" + params.concertoConnection.id + ").defaultReturnFunction == \"1\"}' " +
                      "ng-click='editConnectionCode(collectionService.getConnection(" + params.concertoConnection.id + "))' " +
                      "uib-tooltip-html='collectionService.getConnection(" + params.concertoConnection.id + ").returnFunction' tooltip-append-to-body='true'></i></div>" +
                      "</div>");
                  $compile(overlayElem)(scope);
                  return overlayElem;
                },
                location: 0.5,
                id: "overlayConnection" + params.concertoConnection.id
              }]);
        } else if (!params.sourcePort || params.sourcePort.variableObject.type == 2) {
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

        $http.post(Paths.TEST_FLOW_CONNECTION_DELETE_COLLECTION.pf(id), {}).success(function (data) {
          if (data.result === 0) {
            $("#overlayConnection" + id).remove();
            for (var i = 0; i < scope.object.nodesConnections.length; i++) {
              var connection = scope.object.nodesConnections[i];
              if (connection.id == id) {
                scope.object.nodesConnections.splice(i, 1);
                break;
              }
            }
          }
        });
      };

      scope.toggleUnconnectedPortsCollapse = function (nodeId) {
        var node = scope.collectionService.getNode(nodeId);
        var expanded = !node.expanded;
        node.expanded = expanded;

        scope.refreshNode(node);
      };

      scope.isPortVisible = function (node, port) {
        //input
        if ((node.type == 0 && port.variableObject.type == 0) || (node.type == 2 && port.variableObject.type == 1)) {
          if (node.expanded || scope.isPortConnected(port))
            return true;
        }
        //returns
        if ((node.type == 0 && port.variableObject.type == 1) || (node.type == 1 && port.variableObject.type == 0)) {
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
          if (!scope.jsPlumbEventsEnabled)
            return;
          if (!info.dropEndpoint || info.connection.endpoints.length === 0)
            return false;

          var sourceParams = info.connection.endpoints[0].getParameters();
          var targetParams = info.dropEndpoint.getParameters();

          var sourcePortType = null;
          var sourceNodeType = sourceParams.sourceNode.type;

          if (!sourceParams.sourcePort || !sourceParams.sourcePort.variableObject) {
            sourcePortType = 2;
          } else {
            sourcePortType = sourceParams.sourcePort.variableObject.type;
          }

          var targetPortType = null;
          if (targetParams.targetPort && targetParams.targetPort.variableObject)
            targetPortType = targetParams.targetPort.variableObject.type;
          var targetNodeType = targetParams.targetNode.type;
          if (sourceNodeType == 1) {
            if (sourcePortType == 0)
              sourcePortType = 1;
          }
          if (targetNodeType == 2) {
            if (targetPortType == 1)
              targetPortType = 0;
          }

          switch (parseInt(sourcePortType)) {
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
          if (!scope.jsPlumbEventsEnabled)
            return;
          var params = info.connection.getParameters();
          if (!params.concertoConnection) {
            scope.addConnection(params.concertoConnection, info.connection);
            return;
          }
          scope.setUpConnection(info.connection);
        });

        jsPlumb.bind("connectionMoved", function (info) {
          if (!scope.jsPlumbEventsEnabled)
            return;
          var params = info.connection.getParameters();
          scope.saveConnection(params.concertoConnection, info.connection);
        });

        jsPlumb.bind("connectionDetached", function (info) {
          if (!scope.jsPlumbEventsEnabled)
            return;
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

      scope.lastScrollTop = 0;
      scope.lastScrollLeft = 0;
      $(function () {
        $("#flowContainerScroll").flow();

        /** IE fix start */
        $('#flowContainerScroll').scroll(function () {
          scope.lastScrollTop = $("#flowContainerScroll").scrollTop();
          scope.lastScrollLeft = $("#flowContainerScroll").scrollLeft();
        });

        $('#flowContainer').focus(function () {
          $('#flowContainerScroll').scrollLeft(scope.lastScrollLeft);
          $('#flowContainerScroll').scrollTop(scope.lastScrollTop);
        });
        /** IE fix end */

        $('#flowContainer').mousewheel(function (event) {
          scope.setZoom(event.deltaY);
          return false;
        }).mousemove(function (event) {
          scope.currentMouseEvent = event;
        });
      });

      scope.$watchCollection("object.variables", function () {
        scope.initialized = false;
        if (scope.object.nodes.length > 0) {
          scope.refreshFlow();
        }
      });

      scope.$on('$locationChangeStart', function (event, toUrl, fromUrl) {
        if (scope.maximized)
          scope.toggleMaximize();
      });
    }
  };
}]);