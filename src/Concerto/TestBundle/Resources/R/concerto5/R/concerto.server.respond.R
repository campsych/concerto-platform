concerto.server.respond = function(response, data=list()){
  concerto.log("responding to server...")
  con = socketConnection(host="localhost", port=concerto$session$testNodePort)
  response = list("source"=SOURCE_PROCESS, "code"=response, "data"=data)
  writeLines(paste(toJSON(response),"\n",sep=''),con)
  close(con)
  concerto.log("responded to server")
}