require(concerto5)

concerto5:::concerto.init(
    connectionParams = fromJSON(commandArgs(TRUE)[3]),
    publicDir = commandArgs(TRUE)[1],
    mediaUrl = commandArgs(TRUE)[2],
    maxExecTime = as.numeric(commandArgs(TRUE)[4]),
    maxIdleTime = 0,
    keepAliveToleranceTime = 0,
    runnerType = 2
)

switch(concerto$connectionParams$driver,
    pdo_mysql = require("RMySQL"),
    pdo_sqlsrv = require("RSQLServer")
)

concerto.log(paste0("waiting for submitter port..."))
initPort = NULL
file.remove("checkpoint.lock")
while(T) {
    if(file.exists("submitter.port")) {
        fh = file("submitter.port", open="rt")
        initPort = readLines(fh)
        close(fh)
        unlink("submitter.port")
        break
    }
    Sys.sleep(0.1)
}

concerto.log(paste0("waiting for submit (port: ", initPort, ")..."))
con = socketConnection("localhost", initPort, blocking = TRUE, timeout = 60 * 60 * 24, open = "r")
rm(initPort)
response = fromJSON(readLines(con, warn = FALSE))
close(con)
rm(con)

concerto5:::concerto.run(
    workingDir = response$workingDir,
    client = fromJSON(response$client),
    sessionHash = response$sessionHash
)