concerto.server.listen = function(){
  print("listening to server...")

  dbDisconnect(concerto$connection)
  closeAllConnections()
  print("connections closed")

  print(paste0("waiting for response from ",concerto$submitter.host,":",concerto$submitter.port))
  con = socketConnection("localhost", concerto$submitter.port, blocking=TRUE, timeout=60*60*24, open="r")
  response = readLines(con,warn=FALSE) 
  response <- fromJSON(response)
  close(con)

  print("received response")
  print(response)

  connection <- fromJSON(commandArgs(TRUE)[1])
  concerto$connection <<- concerto5:::concerto.db.connect(connection$driver, connection$username, connection$password, connection$dbname, connection$host, connection$unix_socket, connection$port)
  rm(connection)
 
  if(response$code == RESPONSE_SERIALIZE){
    concerto5:::concerto.session.serialize()
  }
  
  print("listened to server")
  if(response$code == RESPONSE_SUBMIT) return(fromJSON(response$values))
  else return(response)
}