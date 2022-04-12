concerto.init = function(dbConnectionParams, publicDir, platformUrl, appUrl, maxExecTime, maxIdleTime, keepAliveToleranceTime, sessionStorage, redisConnectionParams, sessionFilesExpiration, serviceFifoDir){
    SOURCE_PROCESS <<- 1
    SOURCE_SERVER <<- 2

    RESPONSE_VIEW_TEMPLATE <<- 0
    RESPONSE_FINISHED <<- 1
    RESPONSE_SUBMIT <<- 2
    RESPONSE_STOP <<- 3
    RESPONSE_STOPPED <<- 4
    RESPONSE_VIEW_FINAL_TEMPLATE <<- 5
    RESPONSE_KEEPALIVE_CHECKIN <<- 10
    RESPONSE_SESSION_LOST <<- 14
    RESPONSE_WORKER <<- 15
    RESPONSE_RESUME <<- 16
    RESPONSE_ERROR <<- -1

    STATUS_RUNNING <<- 0
    STATUS_STOPPED <<- 1
    STATUS_FINALIZED <<- 2
    STATUS_ERROR <<- 3

    RUNNER_PERSISTENT <<- 0
    RUNNER_SERIALIZED <<- 1

    tempdir(T)

    concerto <<- list()
    concerto$cache <<- list(tests=list(), templates=list(), tables=list())
    concerto$cacheEnabled <<- T
    concerto$globals <<- list()
    concerto$templateParams <<- list()
    concerto$globalTemplateParams <<- list()
    concerto$flow <<- list()
    concerto$flowIndex <<- 0
    concerto$bgWorkers <<- list()
    concerto$queuedResponse <<- NULL
    concerto$skipTemplateOnResume <<- F
    concerto$response <<- list()
    concerto$serviceRequestId <<- 0
    concerto$serviceFifoDir <<- serviceFifoDir

    concerto$publicDir <<- publicDir
    concerto$platformUrl <<- platformUrl
    concerto$appUrl <<- appUrl
    concerto$mediaUrl <<- paste0(platformUrl, "/bundles/concertopanel/files")
    concerto$maxExecTime <<- maxExecTime
    concerto$maxIdleTime <<- maxIdleTime
    concerto$keepAliveToleranceTime <<- keepAliveToleranceTime
    concerto$lastSubmitTime <<- as.numeric(Sys.time())
    concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
    concerto$dbConnectionParams <<- dbConnectionParams
    concerto$sessionStorage <<- sessionStorage
    concerto$redisConnectionParams <<- redisConnectionParams
    concerto$sessionFilesExpiration <<- sessionFilesExpiration

    concerto$events <<- list(
        onBeforeTemplateShow=NULL,
        onTemplateSubmit=NULL
    )
}