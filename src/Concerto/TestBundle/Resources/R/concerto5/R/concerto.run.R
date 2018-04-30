concerto.run = function(workingDir, client, sessionHash) {
    concerto$workingDir <<- workingDir
    concerto$client <<- client
    concerto$sessionHash <<- sessionHash
    concerto$sessionFile <<- paste0(concerto$workingDir,"session.Rs")

    concerto$connection <<- concerto5:::concerto.db.connect(concerto$connectionParams$driver, concerto$connectionParams$username, concerto$connectionParams$password, concerto$connectionParams$dbname, concerto$connectionParams$host, concerto$connectionParams$unix_socket, concerto$connectionParams$port)
    concerto$session <<- as.list(concerto5:::concerto.session.get(concerto$sessionHash))
    concerto$session$previousStatus <<- concerto$session$status
    concerto$session$status <<- STATUS_RUNNING
    concerto$session$params <<- fromJSON(concerto$session$params)

    concerto$flow <<- list()
    concerto$cache <<- list(tables=list(), templates=list(), tests=list())

    tryCatch({
        setwd(concerto$workingDir)
        setTimeLimit(elapsed=concerto$maxExecTime, transient=TRUE)
        concerto.test.run(concerto$session["test_id"], concerto$session$params, TRUE)

        concerto5:::concerto.session.stop(STATUS_FINALIZED, RESPONSE_FINISHED)
    }, error = function(e) {
        concerto.log(e)
        concerto$session$error <<- e
        concerto5:::concerto.session.stop(STATUS_ERROR, RESPONSE_ERROR)
    })
}