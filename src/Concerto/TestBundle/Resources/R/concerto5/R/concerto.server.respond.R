concerto.server.respond = function(response){
  print("responding to server...")
  con = socketConnection(host=concerto$r_server.host, port=concerto$r_server.port)
  response = list("source"=SOURCE_PROCESS, "code"=response)
  writeLines(paste(toJSON(response),"\n",sep=''),con)
  close(con)
  print("responded to server")
}