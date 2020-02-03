concerto.server.respond = function(response, data=list()){
  concerto.log("responding to server...")

  port = concerto$initialPort
  if(!is.null(concerto$session)) {
      port = concerto$session$submitterPort
  }
  if(concerto$runnerType == RUNNER_SERIALIZED && file.exists("submitter.port")) {
    while(T) {
        fh = file("submitter.port", open="rt")
        port = readLines(fh)
        if(!is.null(concerto$session)) { concerto$session$submitterPort <<- port }
        close(fh)
        if(length(port) == 0) {
           Sys.sleep(0.1)
           next
        }
        unlink("submitter.port")
        break
    }
  }
  con = socketConnection(host="localhost", port=port)
  response = list(
    source=SOURCE_PROCESS,
    code=response,
    data=data
  )

  writeLines(paste0(toJSON(response), "\n"), con)
  close(con)
  concerto.log("responded to server")
}