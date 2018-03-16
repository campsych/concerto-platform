concerto.server.listen = function(){
    concerto.log("listening to server...")

    dbDisconnect(concerto$connection)
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

    concerto$connection <<- concerto5:::concerto.db.connect(concerto$connectionParams$driver, concerto$connectionParams$username, concerto$connectionParams$password, concerto$connectionParams$dbname, concerto$connectionParams$host, concerto$connectionParams$unix_socket, concerto$connectionParams$port)

    if (response$code == RESPONSE_STOP) {
        concerto$session$status <<- STATUS_STOPPED
        concerto5:::concerto.session.update()
        dbDisconnect(concerto$connection)
        concerto.log("stopped")
        q("no")
    }

    concerto.log("listened to server")
    return(response)
}