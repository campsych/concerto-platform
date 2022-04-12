concerto.session.unserialize <- function(response = NULL, hash = NULL){
    concerto.log("unserializing session...")

    prevEnv = new.env()
    if(concerto$sessionStorage == "redis") {
        #TODO decompress
        redisBinarySession = concerto$redisConnection$GET(concerto$session$hash)
        if(!is.null(redisBinarySession)) {
            prevEnv$concerto = unserialize(redisBinarySession)
        } else {
            return(F)
        }
    } else {
        sessionFileName = concerto$sessionFile
        if(!is.null(hash)) {
            sessionFileName = gsub(concerto$session$hash, hash, sessionFileName)
        }
        if(!file.exists(sessionFileName)) {
            concerto.log(sessionFileName, "session file not found")
            return(F)
        }
        concerto.log(sessionFileName)

        load(sessionFileName, envir=prevEnv)
    }

    concerto$cache <<- prevEnv$concerto$cache
    concerto$globals <<- prevEnv$concerto$globals
    concerto$templateParams <<- prevEnv$concerto$templateParams
    concerto$globalTemplateParams <<- prevEnv$concerto$globalTemplateParams
    concerto$flow <<- prevEnv$concerto$flow
    concerto$lastSubmitTime <<- prevEnv$concerto$lastSubmitTime
    concerto$lastSubmitResult <<- prevEnv$lastSubmitResult
    concerto$lastSubmitId <<- prevEnv$lastSubmitId
    concerto$lastKeepAliveTime <<- prevEnv$concerto$lastKeepAliveTime
    concerto$bgWorkers <<- prevEnv$concerto$bgWorkers
    concerto$headers <<- prevEnv$concerto$headers
    if(!is.null(response)) {
        concerto$lastResponse <<- response
    } else {
        concerto$lastResponse <<- prevEnv$concerto$lastResponse
    }
    concerto$skipTemplateOnResume <<- prevEnv$concerto$skipTemplateOnResume
    concerto$events <<- prevEnv$concerto$events
    rm(prevEnv)

    concerto.log("session unserialized")

    #non submit resume
    if(is.null(response) && concerto$runnerType == RUNNER_SERIALIZED) {
        concerto$resuming <<- T
        concerto$resumeIndex <<- 0
        concerto.test.run(concerto$flow[[1]]$id, params=concerto$flow[[1]]$params)
        concerto5:::concerto.session.stop(STATUS_FINALIZED, RESPONSE_FINISHED)
    }

    if (!is.null(response$code) && response$code == RESPONSE_SUBMIT) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        concerto$lastSubmitTime <<- as.numeric(Sys.time())

        if(!is.null(concerto$lastSubmitId) && concerto$lastSubmitId == response$values$submitId) {
            concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE, concerto$lastSubmitResult)
            concerto5:::concerto.session.stop(STATUS_RUNNING)
        }

        concerto.event.fire("onTemplateSubmit", list(response=response$values))
        concerto$queuedResponse <<- response$values
    } else if(!is.null(response$code) && response$code == RESPONSE_WORKER) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        result = list()
        if(!is.null(response$values$bgWorker) && response$values$bgWorker %in% ls(concerto$bgWorkers)) {
            concerto.log(paste0("running worker: ", response$values$bgWorker))
            result = do.call(concerto$bgWorkers[[response$values$bgWorker]], list(response=response$values))
        }
        concerto5:::concerto.session.serialize()
        concerto5:::concerto.server.respond(RESPONSE_WORKER, result)
        concerto5:::concerto.session.stop(STATUS_RUNNING)
    } else if(!is.null(response$code) && response$code == RESPONSE_RESUME) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        if(concerto$skipTemplateOnResume) {
            concerto$queuedResponse <<- list()
        }
    }

    return(T)
}