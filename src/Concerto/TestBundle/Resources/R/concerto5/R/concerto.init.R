concerto.init = function(connectionParams, publicDir, mediaUrl, maxExecTime, maxIdleTime, keepAliveToleranceTime, runnerType = 0){
    options(digits.secs = 6)
    concerto.log("starting session")
    if(Sys.info()['sysname'] != "Windows") {
        options(encoding='UTF-8')
        Sys.setlocale("LC_ALL","en_US.utf8")
    } else {
        Sys.setlocale("LC_ALL","English")
    }

    assign("fromJSON", function(txt, simplifyVector = FALSE, simplifyDataFrame = simplifyVector,
    simplifyMatrix = simplifyVector, flatten = FALSE, ...){
        result = jsonlite::fromJSON(txt, simplifyVector, simplifyDataFrame, simplifyMatrix, flatten, ...)
        return(result)
    }, envir = .GlobalEnv)

    assign("toJSON", function(x, dataframe = c("rows", "columns", "values"), matrix = c("rowmajor",
    "columnmajor"), Date = c("ISO8601", "epoch"), POSIXt = c("string",
    "ISO8601", "epoch", "mongo"), factor = c("string", "integer"),
    complex = c("string", "list"), raw = c("base64", "hex", "mongo"),
    null = c("list", "null"), na = c("null", "string"), auto_unbox = TRUE,
    digits = 4, pretty = FALSE, force = FALSE, ...) {
        result = jsonlite::toJSON(x, dataframe, matrix, Date, POSIXt, factor, complex, raw, null, na, auto_unbox, digits, pretty, force, ...)
        result = as.character(result)
        return(result)
    }, envir = .GlobalEnv)

    SOURCE_PROCESS <<- 1
    SOURCE_SERVER <<- 2

    RESPONSE_VIEW_TEMPLATE <<- 0
    RESPONSE_FINISHED <<- 1
    RESPONSE_SUBMIT <<- 2
    RESPONSE_STOP <<- 3
    RESPONSE_STOPPED <<- 4
    RESPONSE_VIEW_FINAL_TEMPLATE <<- 5
    RESPONSE_KEEPALIVE_CHECKIN <<- 10
    RESPONSE_UNRESUMABLE <<- 11
    RESPONSE_WORKER <<- 15
    RESPONSE_ERROR <<- -1

    STATUS_RUNNING <<- 0
    STATUS_STOPPED <<- 1
    STATUS_FINALIZED <<- 2
    STATUS_ERROR <<- 3

    RUNNER_PERSISTENT <<- 0
    RUNNER_SERIALIZED <<- 1
    RUNNER_CHECKPOINT <<- 2

    concerto <<- list()
    concerto$cache <<- list(tests=list(), templates=list(), tables=list())
    concerto$promoted <<- list()
    concerto$templateParams <<- list()
    concerto$flow <<- list()
    concerto$bgWorkers <<- list()
    concerto$queuedResponse <<- NULL

    #DEFAULTS START
    concerto$promoted$template_def <<- "{\"layout\":\"default_layout\",\"header\":\"Your header goes here. For example, it could be a logo.\",\"footer\":\"Your footer goes here. For example, it could be a copyright sign. You might also have links to a privacy policy.\"}"
    #DEFAULTS END

    concerto$publicDir <<- publicDir
    concerto$mediaUrl <<- mediaUrl
    concerto$maxExecTime <<- maxExecTime
    concerto$maxIdleTime <<- maxIdleTime
    concerto$keepAliveToleranceTime <<- keepAliveToleranceTime
    concerto$lastSubmitTime <<- as.numeric(Sys.time())
    concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
    concerto$connectionParams <<- connectionParams
    concerto$runnerType <<- runnerType
}