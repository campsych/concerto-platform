concerto.server.respond = function(response){
  print("responding to server...")
  con = socketConnection(host=concerto$test_node.host, port=concerto$test_node.port)
  response = list("source"=SOURCE_PROCESS, "code"=response)
  writeLines(paste(toJSON(response),"\n",sep=''),con)
  close(con)
  print("responded to server")
}