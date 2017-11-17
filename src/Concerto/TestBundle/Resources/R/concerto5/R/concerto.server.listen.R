concerto.server.listen = function(){
    concerto.log("listening to server...")

    dbDisconnect(concerto$connection)
    closeAllConnections()
    concerto.log("connections closed")

    concerto.log(paste0("waiting for response from ", concerto$submitter.host, ":", concerto$submitter.port))
    setTimeLimit(transient = TRUE)
    con = socketConnection("localhost", concerto$submitter.port, blocking = TRUE, timeout = 60 * 60 * 24, open = "r")
    response = readLines(con, warn = FALSE)
    response <- fromJSON(response)
    close(con)
    setTimeLimit(elapsed = concerto$maxExecTime, transient = TRUE)

    concerto.log("received response")
    concerto.log(response)

    connection <- fromJSON(commandArgs(TRUE)[1])
    concerto$connection <<- concerto5:::concerto.db.connect(connection$driver, connection$username, connection$password, connection$dbname, connection$host, connection$unix_socket, connection$port)
    rm(connection)

    if (response$code == RESPONSE_STOP) {
        concerto$session$status <<- STATUS_STOPPED
        concerto5:::concerto.session.update()
        dbDisconnect(concerto$connection)
        concerto.log("stopped")
        concerto5:::concerto.server.respond(RESPONSE_STOPPED)
        stop("stopped")
    }

    concerto.log("listened to server")
    if (response$code == RESPONSE_SUBMIT) return(fromJSON(response$values))
    else return(response)
}