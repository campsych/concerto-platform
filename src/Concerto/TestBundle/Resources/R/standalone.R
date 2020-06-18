require(concerto5)

concerto5:::concerto.init(
    connectionParams = fromJSON(commandArgs(TRUE)[1]),
    publicDir = commandArgs(TRUE)[5],
    platformUrl = commandArgs(TRUE)[6],
    appUrl = commandArgs(TRUE)[7],
    maxExecTime = as.numeric(commandArgs(TRUE)[8]),
    maxIdleTime = as.numeric(commandArgs(TRUE)[9]),
    keepAliveToleranceTime = as.numeric(commandArgs(TRUE)[10])
)

concerto5:::concerto.run(
    workingDir = commandArgs(TRUE)[4],
    client = fromJSON(commandArgs(TRUE)[2]),
    sessionHash = commandArgs(TRUE)[3],
    initialPort = commandArgs(TRUE)[12],
    runnerType = commandArgs(TRUE)[13],
    response = fromJSON(commandArgs(TRUE)[11])
)