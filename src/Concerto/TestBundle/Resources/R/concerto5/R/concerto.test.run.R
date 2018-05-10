concerto.test.run <-
function(testId, params=list(), mainTest=FALSE, ongoingResumeFlowIndex=-1) {
    test <- concerto.test.get(testId, cache=F, includeSubObjects=T)
    if (is.null(test)) stop(paste("Test #", testId, " not found!", sep = ''))
    concerto.log(paste0("running test #", test$id, ": ", test$name, " ..."))

    r <- list()
    testenv = new.env()

    if (dim(test$variables)[1] > 0) {
        for (i in 1 : dim(test$variables)[1]) {
            if (!exists(test$variables[i, "name"]) &&
                !is.null(test$variables[i, "value"]) &&
                test$variables[i, "type"] == 0) {

                assign(test$variables[i, "name"], test$variables[i, "value"], envir = testenv)
                if(!(test$variables[i, "name"] %in% names(params))) {
                    params[[test$variables[i, "name"]]] = test$variables[i, "value"]
                }
            }
        }
    }

    if (length(params) > 0) {
        for (param in ls(params)) {
            assign(param, params[[param]], envir = testenv)
        }
    }

    if (mainTest) {
        concerto$mainTest <<- list(id=test$id)
    }

    flowIndex = length(concerto$flow)
    if (test$type != 1) {
        flowIndex = flowIndex + 1
        if (ongoingResumeFlowIndex != -1) {
            flowIndex = ongoingResumeFlowIndex
        } else {
            concerto$flow[[flowIndex]] <<- list()
            concerto$flow[[flowIndex]]$id <<- test$id
            concerto$flow[[flowIndex]]$type <<- test$type
            concerto$flow[[flowIndex]]$params <<- params
        }
    }

    if (test$type == 1) {
        #wizard
        return(concerto.test.run(test$sourceTest$id, params, mainTest, ongoingResumeFlowIndex))
    } else if (test$type == 0) {
        #code
        eval(parse(text = test$code), envir = testenv)

        if (dim(test$variables)[1] > 0) {
            for (i in 1 : dim(test$variables)[1]) {
                if (test$variables[i, "type"] != 1) { next}
                if (exists(test$variables[i, "name"], envir = testenv)) {
                    r[[test$variables[i, "name"]]] <- get(test$variables[i, "name"], envir = testenv)
                } else if (!is.null(test$variables[i, "value"])) {
                    r[[test$variables[i, "name"]]] <- test$variables[i, "value"]
                }
            }
        }
    } else {
        #flow
        isGetterNode = function(node){
            if (node$type != 0) return(F)
            for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                if (port$node_id == node$id && port$type == 2) return(F)
            }
            return(T)
        }

        runNode = function(node){
            r = list()

            #PARAMS
            node_params = list()
            input_type = 0
            if (node$type == 2) { input_type = 1}

            if (node$type == 0 || node$type == 2) {
                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == input_type) {
                        port_connected = FALSE
                        for (connection_id in ls(concerto$flow[[flowIndex]]$connections)) {
                            connection = concerto$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                            if (!is.na(connection$destinationPort_id) && connection$destinationPort_id == port$id) {
                                port_connected = TRUE

                                #check for getter node
                                source_node = concerto$flow[[flowIndex]]$nodes[[as.character(connection$sourceNode_id)]]
                                if (isGetterNode(source_node)) {
                                    #getters shouldn't be resumable
                                    runNode(source_node)
                                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                                }
                                break
                            }
                        }

                        if (port$string == 0 && !port_connected) {
                            node_params[[port$name]] = eval(parse(text = port$value))
                        } else {
                            node_params[[port$name]] = port$value
                        }
                    }
                }
            }

            #EXECUTION, RETURNS
            if (node$type == 0) {

                if (ongoingResumeFlowIndex != -1 &&
                    length(concerto$flow) > flowIndex &&
                    concerto$flow[[flowIndex]]$type == 2 &&
                    concerto$flow[[flowIndex + 1]]$id == node$sourceTest_id) {
                    node_returns = concerto.test.run(node$sourceTest_id, params = node_params, ongoingResumeFlowIndex = flowIndex + 1)
                } else {
                    node_returns = concerto.test.run(node$sourceTest_id, params = node_params)
                }

                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[port_id]]
                    if (port$node_id == node$id && port$type == 1) {
                        concerto$flow[[flowIndex]]$ports[[as.character(port$id)]]$value <<- node_returns[[port$name]]
                    }
                }
            } else if (node$type == 1) {
                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == 0) {
                        concerto$flow[[flowIndex]]$ports[[as.character(port$id)]]$value <<- params[[port$name]]
                    }
                }
            } else if (node$type == 2) {
                r = node_params
            }

            #BRANCH
            if (node$type != 2) {
                branch_port = NULL
                branch_name = NA
                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]

                    if (port$node_id == node$id &&
                        port$type == 1 &&
                        port$name == ".branch") {
                        branch_name = port$value
                        break
                    }
                }

                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]

                    if (port$node_id == node$id && port$type == 2) {
                        if (is.na(branch_name)) {
                            branch_port = port
                            break
                        } else {
                            if (branch_name == port$name) {
                                branch_port = port
                                break
                            }
                        }
                    }
                }

                for (connection_id in ls(concerto$flow[[flowIndex]]$connections)) {
                    connection = concerto$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                    if (!is.null(branch_port) && !is.na(connection$sourcePort_id) && connection$sourcePort_id == branch_port$id) {
                        concerto$flow[[flowIndex]]$nextNode <<- concerto$flow[[flowIndex]]$nodes[[as.character(connection$destinationNode_id)]]
                        break
                    } else if(node$type == 1 && connection$sourceNode_id == node$id && is.na(connection$sourcePort_id)) {
                        concerto$flow[[flowIndex]]$nextNode <<- concerto$flow[[flowIndex]]$nodes[[as.character(connection$destinationNode_id)]]
                        break
                    }
                }
            }

            #values connections
            return_type = 1
            if (node$type == 1) return_type = 0
            for (connection_id in ls(concerto$flow[[flowIndex]]$connections)) {
                connection = concerto$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                if (connection$sourceNode_id != node$id) { next }
                if (is.na(connection$sourcePort_id)) { next }
                if (concerto$flow[[flowIndex]]$ports[[as.character(connection$sourcePort_id)]]$type != return_type) { next }

                func = paste("retFunc = function(", concerto$flow[[flowIndex]]$ports[[as.character(connection$sourcePort_id)]]$name, "){ ", connection$returnFunction, " }", sep = "")
                eval(parse(text = func))
                concerto$flow[[flowIndex]]$ports[[as.character(connection$destinationPort_id)]]$value <<- retFunc(concerto$flow[[flowIndex]]$ports[[as.character(connection$sourcePort_id)]]$value)
            }
            return(r)
        }

        #persist flow
        if (ongoingResumeFlowIndex == -1) {
            concerto$flow[[flowIndex]]$nodes <<- list()
            concerto$flow[[flowIndex]]$connections <<- list()
            concerto$flow[[flowIndex]]$ports <<- list()

            if (dim(test$nodes)[1] > 0) {
                for (i in 1 : (dim(test$nodes)[1])) {
                    concerto$flow[[flowIndex]]$nodes[[as.character(test$nodes[i, "id"])]] <<- as.list(test$nodes[i,])
                }
            }
            if (dim(test$connections)[1] > 0) {
                for (i in 1 : (dim(test$connections)[1])) {
                    concerto$flow[[flowIndex]]$connections[[as.character(test$connections[i, "id"])]] <<- as.list(test$connections[i,])
                }
            }
            if (dim(test$ports)[1] > 0) {
                for (i in 1 : (dim(test$ports)[1])) {
                    concerto$flow[[flowIndex]]$ports[[as.character(test$ports[i, "id"])]] <<- as.list(test$ports[i,])
                }
            }

            #find begin and finish nodes
            beginNode = NULL
            finishNode = NULL
            if (dim(test$nodes)[1] > 0) {
                for (i in 1 : (dim(test$nodes)[1])) {
                    if (test$nodes[i, "type"] == 1) { beginNode = as.list(test$nodes[i,])}
                    if (test$nodes[i, "type"] == 2) { finishNode = as.list(test$nodes[i,])}
                }
            }

            concerto$flow[[flowIndex]]$currentNode <<- NULL
            concerto$flow[[flowIndex]]$nextNode <<- beginNode
        } else {
            concerto$flow[[flowIndex]]$nextNode <<- concerto$flow[[flowIndex]]$currentNode
        }

        while (!is.null(concerto$flow[[flowIndex]]$nextNode)) {
            node = concerto$flow[[flowIndex]]$nextNode

            concerto$flow[[flowIndex]]$currentNode <<- node
            concerto$flow[[flowIndex]]$nextNode <<- NULL

            r = runNode(node)
        }
    }
    concerto$flow[[flowIndex]] <<- NULL

    concerto.log(paste0("test #", test$id, ": ", test$name, " finished"))
    return(r)
}