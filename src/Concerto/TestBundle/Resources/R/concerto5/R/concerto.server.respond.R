concerto.server.respond = function(response, data=list()){
  concerto.log("responding to server...")
  if(concerto$runnerType == RUNNER_SERIALIZED && file.exists("submitter.port")) {
    while(T) {
        fh = file("submitter.port", open="rt")
        concerto$session$submitterPort <<- readLines(fh)
        close(fh)
        if(length(concerto$session$submitterPort) == 0) {
           Sys.sleep(0.1)
           next
        }
        unlink("submitter.port")
        break
    }
  }
  con = socketConnection(host="localhost", port=concerto$session$submitterPort)
  response = list("source"=SOURCE_PROCESS, "code"=response, "data"=data)
  writeLines(paste(toJSON(response),"\n",sep=''),con)
  close(con)
  concerto.log("responded to server")
}