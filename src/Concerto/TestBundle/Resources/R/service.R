ENV_CONCERTO_R_SERVICE_FIFO_PATH = Sys.getenv("CONCERTO_R_SERVICE_FIFO_PATH")

require(concerto5)

concerto.log("starting service listener")

queue = c()
repeat {
  reqFifoPath = NULL
  if(length(queue) == 0) {
    queue = list.files(ENV_CONCERTO_R_SERVICE_FIFO_PATH, pattern=".*\\.reqfifo", full.names=TRUE)
  }
  if(length(queue) > 0) {
    reqFifoPath = queue[1]
    queue = queue[-1]
  } else {
    Sys.sleep(0.25)
    next
  }

  con = fifo(reqFifoPath, open="rt", blocking=T)
  request = readLines(con)
  close(con)
  unlink(reqFifoPath)

  request = paste0(request, collapse="\n")
  request = charToRaw(request)
  request = unserialize(request)

  env = new.env()
  for(name in ls(request$params)) {
    assign(name, request$params[[name]], envir=env)
  }
  result = NULL
  result = tryCatch(eval(request$expr, envir=env), error = function(ex) {})

  response = list(
    result = result
  )
  serializedResponse = serialize(response, NULL, ascii=T)
  serializedResponse = rawToChar(serializedResponse, T)

  resFifoPath = paste0(concerto$serviceFifoDir, request$sessionHash, "_", request$requestId, ".resfifo")
  con = fifo(resFifoPath, open="wt", blocking=F)
  writeLines(serializedResponse, con)
  close(con)
}

concerto.log("listener service closing")