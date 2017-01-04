concerto.session.serialize <-
function(){
  print("serializing session...")
  if(exists("concerto.onSerialize")) do.call("concerto.onSerialize",list(),envir=.GlobalEnv);
  
  if(concerto$mainTest$resumable == 1) {
    save.session(concerto$sessionFile)
  }
  concerto$session$status <<- STATUS_SERIALIZED
  concerto5:::concerto.session.update()
  dbDisconnect(concerto$connection)
  print("serialization finished")
  concerto5:::concerto.server.respond(RESPONSE_SERIALIZATION_FINISHED)
  stop("serialized")
}
