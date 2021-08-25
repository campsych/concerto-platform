concerto.server.listen = function(skipOnResume=F){
    repeat {
        concerto.log("listening to server...")

        dbDisconnect(concerto$connection)
        concerto.log("connections closed")

        setTimeLimit(transient = TRUE)

        concerto.log(paste0("waiting for submitter port..."))
        repeat {
            if(file.exists("submitter.port")) {
                fh = file("submitter.port", open="rt")
                concerto$session$submitterPort <<- readLines(fh)
                close(fh)
                if(length(concerto$session$submitterPort) == 0) {
                    Sys.sleep(0.1)
                    next
                }
                break
            }

            currentTime = as.numeric(Sys.time())
            if(concerto$maxIdleTime > 0 && currentTime - concerto$lastSubmitTime > concerto$maxIdleTime) {
                concerto.log("idle timeout")
                concerto$connection <<- concerto.db.connect(
                    concerto$dbConnectionParams$driver,
                    concerto$dbConnectionParams$username,
                    concerto$dbConnectionParams$password,
                    concerto$dbConnectionParams$dbname,
                    concerto$dbConnectionParams$host,
                    concerto$dbConnectionParams$unix_socket,
                    concerto$dbConnectionParams$port
                )
                if(concerto$sessionStorage == "redis") {
                    concerto$redisConnection <<- concerto.redis.connect(
                        host = concerto$redisConnectionParams$host,
                        port = concerto$redisConnectionParams$port,
                        password = concerto$redisConnectionParams$password
                    )
                }
                concerto$session <<- as.list(concerto.session.get(concerto$session$hash))
                concerto5:::concerto.session.stop(STATUS_STOPPED)
            }
            if(concerto$keepAliveToleranceTime > 0 && currentTime - concerto$lastKeepAliveTime > concerto$keepAliveToleranceTime) {
                concerto.log("keep alive timeout")
                concerto$connection <<- concerto.db.connect(
                    concerto$dbConnectionParams$driver,
                    concerto$dbConnectionParams$username,
                    concerto$dbConnectionParams$password,
                    concerto$dbConnectionParams$dbname,
                    concerto$dbConnectionParams$host,
                    concerto$dbConnectionParams$unix_socket,
                    concerto$dbConnectionParams$port
                )
                if(concerto$sessionStorage == "redis") {
                    concerto$redisConnection <<- concerto.redis.connect(
                        host = concerto$redisConnectionParams$host,
                        port = concerto$redisConnectionParams$port,
                        password = concerto$redisConnectionParams$password
                    )
                }
                concerto$session <<- as.list(concerto.session.get(concerto$session$hash))
                concerto5:::concerto.session.stop(STATUS_STOPPED)
            }
            Sys.sleep(0.1)
        }
        concerto.log(paste0("waiting for submit (port: ",concerto$session$submitterPort,")..."))
        con = socketConnection(host = "localhost", port = concerto$session$submitterPort, blocking = TRUE, timeout = 60 * 60 * 24, open = "rt")
        response = readLines(con, warn = FALSE)
        response = fromJSON(response)
        concerto$lastResponse <<- response
        response$values$.cookies = response$cookies
        close(con)
        if(concerto$maxExecTime > 0) {
            setTimeLimit(elapsed = concerto$maxExecTime, transient = TRUE)
        }

        concerto.log(response, "received response")

        concerto$connection <<- concerto.db.connect(
            concerto$dbConnectionParams$driver,
            concerto$dbConnectionParams$username,
            concerto$dbConnectionParams$password,
            concerto$dbConnectionParams$dbname,
            concerto$dbConnectionParams$host,
            concerto$dbConnectionParams$unix_socket,
            concerto$dbConnectionParams$port
        )
        if(concerto$sessionStorage == "redis") {
            concerto$redisConnection <<- concerto.redis.connect(
                host = concerto$redisConnectionParams$host,
                port = concerto$redisConnectionParams$port,
                password = concerto$redisConnectionParams$password
            )
        }
        concerto$session <<- as.list(concerto.session.get(concerto$session$hash))

        concerto.log("listened to server")
        unlink("submitter.port")

        if (response$code == RESPONSE_SUBMIT) {
            concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
            concerto$lastSubmitTime <<- as.numeric(Sys.time())

            if(!is.null(concerto$lastSubmitId) && concerto$lastSubmitId == response$values$submitId) {
                concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE, concerto$lastSubmitResult)
                next
            }

            concerto.event.fire("onTemplateSubmit", list(response=response$values))
            return(response$values)
        } else if (response$code == RESPONSE_RESUME) {
            concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
            response = NULL
            if(skipOnResume) {
                response = list()
            }
            return(response)
        } else if(response$code == RESPONSE_KEEPALIVE_CHECKIN) {
            concerto.log("keep alive checkin")
            concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        } else if(response$code == RESPONSE_STOP) {
            concerto.log("stop request")
            concerto5:::concerto.session.stop(STATUS_STOPPED)
        } else if(response$code == RESPONSE_WORKER) {
            concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
            result = list()
            if(!is.null(response$values$bgWorker) && response$values$bgWorker %in% ls(concerto$bgWorkers)) {
                concerto.log(paste0("running worker: ", response$values$bgWorker))
                result = do.call(concerto$bgWorkers[[response$values$bgWorker]], list(response=response$values))
            }
            concerto5:::concerto.server.respond(RESPONSE_WORKER, result)
        } else return(response)
    }
}