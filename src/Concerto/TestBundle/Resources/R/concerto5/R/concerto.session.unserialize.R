concerto.session.unserialize <- function(response){
    concerto.log("unserializing session...")

    if(!file.exists(concerto$sessionFile)) {
        concerto.log("starting new session")
        return(F)
    }
    con = file(concerto$sessionFile, open="rb")
    prevConcerto = unserialize(con)
    close(con)

    concerto$cache <<- prevConcerto$cache
    concerto$promoted <<- prevConcerto$promoted
    concerto$templateParams <<- prevConcerto$templateParams
    concerto$flow <<- prevConcerto$flow
    concerto$lastSubmitTime <<- prevConcerto$lastSubmitTime
    concerto$lastKeepAliveTime <<- prevConcerto$lastKeepAliveTime
    concerto$bgWorkers <<- prevConcerto$bgWorkers

    concerto.log("session unserialized")

    if (response$code == RESPONSE_SUBMIT) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        concerto$lastSubmitTime <<- as.numeric(Sys.time())
        values = fromJSON(response$values)
        if(exists("concerto.onTemplateSubmit")) {
            do.call("concerto.onTemplateSubmit",list(response=values), envir = .GlobalEnv)
        }
        concerto$queuedResponse <<- values
    } else if(response$code == RESPONSE_WORKER) {
        concerto$lastKeepAliveTime <<- as.numeric(Sys.time())
        values = fromJSON(response$values)
        result = list()
        if(!is.null(values$bgWorker) && values$bgWorker %in% ls(bgWorkers)) {
            concerto.log(paste0("running worker: ",values$bgWorker))
            result = do.call(bgWorkers[[values$bgWorker]], list(response=values))
        }
        concerto5:::concerto.server.respond(RESPONSE_WORKER, result)
        concerto5:::concerto.session.serialize()
        concerto5:::concerto.session.stop(STATUS_RUNNING)
    }

    return(T)
}