require(concerto5)
require(parallel)

concerto5:::concerto.init(
    connectionParams = fromJSON(commandArgs(TRUE)[4]),
    publicDir = commandArgs(TRUE)[2],
    platformUrl = commandArgs(TRUE)[3],
    maxExecTime = as.numeric(commandArgs(TRUE)[5]),
    maxIdleTime = as.numeric(commandArgs(TRUE)[6]),
    keepAliveToleranceTime = as.numeric(commandArgs(TRUE)[7])
)

switch(concerto$connectionParams$driver,
    pdo_mysql = require("RMySQL"),
    pdo_sqlsrv = require("RSQLServer")
)

concerto.log("starting forker listener")
queue = c()
unlink(paste0(commandArgs(TRUE)[1],"*.fifo"))
while (T) {
    fpath = ""
    if(length(queue) == 0) {
        queue = list.files(commandArgs(TRUE)[1], full.names=TRUE)
    }
    if(length(queue) > 0) {
        fpath = queue[1]
        queue = queue[-1]
    } else {
        Sys.sleep(0.25)
        next
    }
    con = fifo(fpath, blocking=TRUE, open="rt")
    response = readLines(con, warn = FALSE, n = 1, ok = TRUE)
    close(con)
    rm(con)
    unlink(fpath)
    rm(fpath)

    if(length(response) == 0) {
        concerto.log(response, "invalid request")
        next
    }

    response = tryCatch({
        fromJSON(response)
    }, error = function(e) {
        message(e)
        message(response)
        q("no", 1)
    })

    if(is.null(response$rLogPath)) response$rLogPath = "/dev/null"

    mcparallel({
        sinkFile <- file(response$rLogPath, open = "at")
        sink(file = sinkFile, append = TRUE, type = "output", split = FALSE)
        sink(file = sinkFile, append = TRUE, type = "message", split = FALSE)
        rm(queue)
        rm(sinkFile)

        concerto$lastSubmitTime <- as.numeric(Sys.time())
        concerto$lastKeepAliveTime <- as.numeric(Sys.time())
        concerto5:::concerto.run(
            workingDir = response$workingDir,
            client = response$client,
            sessionHash = response$sessionId,
            maxIdleTime = response$maxIdleTime,
            maxExecTime = response$maxExecTime,
            response = response$response,
            initialPort = response$initialPort,
            runnerType = response$runnerType
        )
    }, detached = TRUE)
}
concerto.log("listener closing")