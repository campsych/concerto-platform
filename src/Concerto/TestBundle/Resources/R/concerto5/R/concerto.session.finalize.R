concerto.session.finalize <- function(response = RESPONSE_FINISHED){
  concerto.log("finalizing session...")

  #closeAllConnections()
  
  concerto$session$status <<- STATUS_FINALIZED
  concerto5:::concerto.session.update()
  dbDisconnect(concerto$connection)
  concerto.log("session finalized")

  concerto5:::concerto.server.respond(response)
  q("no")
}
