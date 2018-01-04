concerto.server.respond = function(response, data=list()){
  concerto.log("responding to server...")
  con = socketConnection(host=concerto$test_node.host, port=concerto$test_node.port)
  response = list("source"=SOURCE_PROCESS, "code"=response, "data"=data)
  writeLines(paste(toJSON(response),"\n",sep=''),con)
  close(con)
  concerto.log("responded to server")
}