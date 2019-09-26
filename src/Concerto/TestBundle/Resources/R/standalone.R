require(concerto5)

concerto5:::concerto.init(
    connectionParams = fromJSON(commandArgs(TRUE)[1]),
    publicDir = commandArgs(TRUE)[5],
    mediaUrl = commandArgs(TRUE)[6],
    maxExecTime = as.numeric(commandArgs(TRUE)[7]),
    maxIdleTime = as.numeric(commandArgs(TRUE)[8]),
    keepAliveToleranceTime = as.numeric(commandArgs(TRUE)[9]),
    apiUrl = commandArgs(TRUE)[13]
)

concerto5:::concerto.run(
    workingDir = commandArgs(TRUE)[4],
    client = fromJSON(commandArgs(TRUE)[2]),
    sessionHash = commandArgs(TRUE)[3],
    initialPort = commandArgs(TRUE)[11],
    runnerType = commandArgs(TRUE)[12]
)