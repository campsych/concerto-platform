concerto.test.run <-
function(testId, params=list(), extraReturns=c(), mainTest=FALSE, ongoingResumeFlowIndex=-1) {
    test <- concerto.test.get(testId, cache=F, includeSubObjects=T)
    if (is.null(test)) stop(paste("Test #", testId, " not found!", sep = ''))
    concerto.log(paste0("running test #", test$id, ": ", test$name, " ..."))

    r <- list()
    testenv = new.env()

    if (dim(test$variables)[1] > 0) {
        for (i in 1 : dim(test$variables)[1]) {
            if (!is.null(test$variables[i, "value"]) &&
                test$variables[i, "type"] == 0) {

                assign(test$variables[i, "name"], test$variables[i, "value"], envir = testenv)
                if(!(test$variables[i, "name"] %in% names(params))) {
                    params[[test$variables[i, "name"]]] = test$variables[i, "value"]
                }
            }
        }
    }

    if(!(".inputs" %in% ls(params, all.names=T))) {
        params[[".inputs"]] = ls(params, all.names=T)
    }
    if(!(".returns" %in% ls(params, all.names=T))) {
        params[[".returns"]] = c()
        if (dim(test$variables)[1] > 0) {
            for (i in 1 : dim(test$variables)[1]) {
                if (test$variables[i, "type"] == 1) {
                    params[[".returns"]] = c(params[[".returns"]], test$variables[i, "name"])
                }
            }
        }
    }
    if(!(".branches" %in% ls(params, all.names=T))) {
        params[[".branches"]] = c()
        if (dim(test$variables)[1] > 0) {
            for (i in 1 : dim(test$variables)[1]) {
                if (test$variables[i, "type"] == 2) {
                    params[[".branches"]] = c(params[[".branches"]], test$variables[i, "name"])
                }
            }
        }
    }
    if(!(".dynamicInputs" %in% ls(params, all.names=T))) { params[".dynamicInputs"] = list(NULL) }
    if(!(".dynamicReturns" %in% ls(params, all.names=T))) { params[".dynamicReturns"] = list(NULL) }
    if(!(".dynamicBranches" %in% ls(params, all.names=T))) { params[".dynamicBranches"] = list(NULL) }

    if (length(params) > 0) {
        for (param in ls(params, all.names=T)) {
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
            concerto$flow[[flowIndex]] <<- list(
                id = test$id,
                type = test$type,
                params = params,
                globals = list()
            )
            if (length(params) > 0) {
                for (param in ls(params, all.names=T)) {
                    c.set(param,  params[[param]])
                }
            }
        }
    }

    if (test$type == 1) {
        #wizard
        return(concerto.test.run(test$sourceTest$id, params, extraReturns, mainTest, ongoingResumeFlowIndex))
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
        if(length(extraReturns) > 0) {
             for (i in 1 : length(extraReturns)) {
                if (exists(extraReturns[i], envir = testenv)) {
                    r[[extraReturns[i]]] <- get(extraReturns[i], envir = testenv)
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

        evalPortValue = function(port, inserts = list()) {
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

            if (port_connected) {
                return(port$value)
            } else {
                if (port$string == 0) {
                    portEnv = new.env()
                    for(insertName in ls(inserts)) {
                        assign(insertName, inserts[[insertName]], envir=portEnv)
                    }
                    return(eval(parse(text = port$value), envir=portEnv))
                } else {
                    return(concerto.template.insertParams(port$value, inserts, removeMissing=F))
                }
            }
        }

        runNode = function(node){
            r = list()

            #PARAMS
            node_params = list(
                .inputs=c(),
                .returns=c(),
                .branches=c(),
                .dynamicInputs=c(),
                .dynamicReturns=c(),
                .dynamicBranches=c()
            )
            input_type = 0
            dynamicInputs = list()
            dynamicReturns = c()
            dynamicBranches = c()

            for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                if (port$node_id != node$id) next

                if (port$type == input_type && port$dynamic == 1) {
                    portValue = evalPortValue(port)
                    if(is.null(portValue)) {
                        node_params[port$name] = list(NULL)
                        dynamicInputs[port$name] = list(NULL)
                    } else {
                        node_params[[port$name]] = portValue
                        dynamicInputs[[port$name]] = portValue
                    }
                    node_params[[".inputs"]] = c(node_params[[".inputs"]], port$name)
                    node_params[[".dynamicInputs"]] = c(node_params[[".dynamicInputs"]], port$name)
                } else if (port$type == 1) {
                    if (port$dynamic == 1) {
                        dynamicReturns = c(dynamicReturns, port$name)
                        node_params[[".dynamicReturns"]] = c(node_params[[".dynamicReturns"]], port$name)
                    }
                    node_params[[".returns"]] = c(node_params[[".returns"]], port$name)
                } else if (port$type == 2) {
                    if (port$dynamic == 1) {
                        dynamicBranches = c(dynamicBranches, port$name)
                        node_params[[".dynamicBranches"]] = c(node_params[[".dynamicBranches"]], port$name)
                    }
                    node_params[[".branches"]] = c(node_params[[".branches"]], port$name)
                }
            }
            for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                if (port$node_id == node$id && port$type == input_type && port$dynamic == 0) {
                    portValue = evalPortValue(port, dynamicInputs)
                    if(is.null(portValue)) {
                        node_params[port$name] = list(NULL)
                    } else {
                        node_params[[port$name]] = portValue
                    }
                    node_params[[".inputs"]] = c(node_params[[".inputs"]], port$name)
                }
            }

            #EXECUTION, RETURNS
            if (node$type == 0) {
                if (ongoingResumeFlowIndex != -1 &&
                    length(concerto$flow) > flowIndex &&
                    concerto$flow[[flowIndex]]$type == 2 &&
                    concerto$flow[[flowIndex + 1]]$id == node$sourceTest_id) {
                    node_returns = concerto.test.run(node$sourceTest_id, params = node_params, extraReturns=dynamicReturns, ongoingResumeFlowIndex = flowIndex + 1)
                } else {
                    node_returns = concerto.test.run(node$sourceTest_id, params = node_params, extraReturns=dynamicReturns)
                }

                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == 1) {
                        concerto$flow[[flowIndex]]$ports[[as.character(port$id)]]$value <<- node_returns[[port$name]]
                    }
                }
            } else if (node$type == 1) {
                for (port_id in ls(concerto$flow[[flowIndex]]$ports)) {
                    port = concerto$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == 1) {
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
                        if (is.null(branch_name) || is.na(branch_name)) {
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
            for (connection_id in ls(concerto$flow[[flowIndex]]$connections)) {
                connection = concerto$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                if (connection$sourceNode_id != node$id) { next }
                if (is.na(connection$sourcePort_id)) { next }
                if (concerto$flow[[flowIndex]]$ports[[as.character(connection$sourcePort_id)]]$type != return_type) { next }

                func = paste0("retFunc = function(", concerto$flow[[flowIndex]]$ports[[as.character(connection$sourcePort_id)]]$name, "){ ", connection$returnFunction, " }")
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
    if(length(extraReturns) > 0) {
        for (i in 1 : length(extraReturns)) {
            name = extraReturns[i]
            val = c.get(name)
            if (is.null(r[[name]]) && !is.null(val)) {
                if(is.null(val)) {
                    r[name] <- list(NULL)
                } else {
                    r[[name]] <- val
                }
            }
        }
    }
    concerto$flow[[flowIndex]] <<- NULL

    concerto.log(paste0("test #", test$id, ": ", test$name, " finished"))
    return(r)
}