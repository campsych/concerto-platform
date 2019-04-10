concerto.session.unserialize <- function(response = NULL, hash = NULL){
    concerto.log("unserializing session...")

    sessionFileName = concerto$sessionFile
    if(!is.null(hash)) {
        sessionFileName = gsub(concerto$session$hash, hash, sessionFileName)
    }

    if(!file.exists(sessionFileName)) {
        concerto.log(sessionFileName, "session file not found")
        return(F)
    }

    concerto.log(sessionFileName)

    con = file(sessionFileName, open="rb")
    prevConcerto = unserialize(con)
    close(con)

    concerto$cache <<- prevConcerto$cache
    concerto$globals <<- prevConcerto$globals
    concerto$templateParams <<- prevConcerto$templateParams
    concerto$flow <<- prevConcerto$flow
    concerto$lastSubmitTime <<- prevConcerto$lastSubmitTime
    concerto$lastKeepAliveTime <<- prevConcerto$lastKeepAliveTime
    concerto$bgWorkers <<- prevConcerto$bgWorkers

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
        if(exists("concerto.onTemplateSubmit")) {
            do.call("concerto.onTemplateSubmit",list(response=response$values), envir = .GlobalEnv)
        }
        concerto$queuedResponse <<- response$values
    } else if(!is.null(response$code) && response$code == RESPONSE_WORKER) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        result = list()
        if(!is.null(response$values$bgWorker) && response$values$bgWorker %in% ls(concerto$bgWorkers)) {
            concerto.log(paste0("running worker: ", response$values$bgWorker))
            result = do.call(concerto$bgWorkers[[response$values$bgWorker]], list(response=response$values))
        }
        concerto5:::concerto.server.respond(RESPONSE_WORKER, result)
        concerto5:::concerto.session.serialize()
        concerto5:::concerto.session.stop(STATUS_RUNNING)
    }

    return(T)
}