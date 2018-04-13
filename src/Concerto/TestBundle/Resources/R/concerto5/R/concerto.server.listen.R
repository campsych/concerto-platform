concerto.server.listen = function(){
    concerto.log("listening to server...")

    dbDisconnect(concerto$connection)
    concerto.log("connections closed")

    concerto.log(paste0("waiting for response from port:", concerto$session$submitterPort))
    setTimeLimit(transient = TRUE)
    port = NULL

    while(T) {
        if(file.exists("php.port")) {
            fh = file("php.port", open="rt")
            port = readLines(fh)
            unlink("php.port")
            break
        }

        currentTime = as.numeric(Sys.time())
        if(currentTime - concerto$lastSubmitTime > concerto$maxIdleTime) {
            concerto.log("idle timeout")
            concerto5:::concerto.session.stop(STATUS_STOPPED)
        }
        if(currentTime - concerto$lastKeepAliveTime > concerto$keepAliveToleranceTime) {
            concerto.log("keep alive timeout")
            concerto5:::concerto.session.stop(STATUS_STOPPED)
        }
        Sys.sleep(0.1)
    }
    con = socketConnection("localhost", port, blocking = TRUE, timeout = 60 * 60 * 24, open = "r")
    response = readLines(con, warn = FALSE)
    response <- fromJSON(response)
    close(con)
    setTimeLimit(elapsed = concerto$maxExecTime, transient = TRUE)

    concerto.log("received response")
    concerto.log(response)

    concerto$connection <<- concerto5:::concerto.db.connect(concerto$connectionParams$driver, concerto$connectionParams$username, concerto$connectionParams$password, concerto$connectionParams$dbname, concerto$connectionParams$host, concerto$connectionParams$unix_socket, concerto$connectionParams$port)
    concerto$session <<- as.list(concerto.session.get(concerto$session$hash))

    concerto.log("listened to server")

    if (response$code == RESPONSE_SUBMIT) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        concerto$lastSubmitTime <<- as.numeric(Sys.time())
        values = fromJSON(response$values)
        if(exists("concerto.onTemplateSubmit")) {
            do.call("concerto.onTemplateSubmit",list(response=values), envir = .GlobalEnv)
        }
        return(values)
    } else if(response$code == RESPONSE_KEEPALIVE_CHECKIN) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        return(concerto.server.listen())
    } else if(response$code == RESPONSE_STOP) {
        concerto5:::concerto.session.stop(STATUS_STOPPED)
    } else if(response$code == RESPONSE_WORKER) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        values = fromJSON(response$values)
        result = list()
        if(!is.null(values$bgWorker) && values$bgWorker %in% ls(bgWorkers)) {
            concerto.log(paste0("running worker: ",values$bgWorker))
            result = do.call(bgWorkers[[values$bgWorker]], list(response=values))
        }
        concerto5:::concerto.server.respond(RESPONSE_WORKER, result)
        return(concerto.server.listen())
    } else return(response)
}