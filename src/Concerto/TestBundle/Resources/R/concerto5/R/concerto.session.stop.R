concerto.session.stop <- function(status = STATUS_STOPPED, response = NULL){
    concerto.log("stopping session...")
    concerto.log(paste0("status: ", status))

    concerto$session$status <<- status
    concerto5:::concerto.session.update()
    dbDisconnect(concerto$connection)

    if (!is.null(response)) {
        concerto5:::concerto.server.respond(response)
    }
    q("no", if(status == STATUS_ERROR) 1 else 0)
}
