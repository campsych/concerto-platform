concerto.session.finalize <- function(response = RESPONSE_FINISHED, returns = list()){
  print("finalizing session...")
  
  closeAllConnections()
  
  concerto$session$status <<- STATUS_FINALIZED
  concerto5:::concerto.session.update(returns)
  dbDisconnect(concerto$connection)
  print("session finalized")

  concerto5:::concerto.server.respond(response)
  stop("session finalized")
}
