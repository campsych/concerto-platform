concerto.server.listen = function(){
  print("listening to server...")
  closeAllConnections()
  con = socketConnection(concerto$submitter.host, concerto$submitter.port, blocking=TRUE, timeout=60*60*24, open="r")
  
  response = readLines(con,warn=FALSE) 
  close(con)
 
  response <- fromJSON(response)
  if(response$code == RESPONSE_SERIALIZE){
    concerto5:::concerto.session.serialize()
  }
  
  print("listened to server")
  if(response$code == RESPONSE_SUBMIT) return(fromJSON(response$values))
  else return(response)
}