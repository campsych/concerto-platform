ENV_CONCERTO_R_SERVICE_FIFO_PATH = Sys.getenv("CONCERTO_R_SERVICE_FIFO_PATH")

concerto.log("starting service listener")

queue = c()
unlink(paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, "*"))
repeat {
  reqFifoPath = NULL
  if(length(queue) == 0) {
    queue = list.files(ENV_CONCERTO_R_SERVICE_FIFO_PATH, pattern=".*\\.reqfifo", full.names=TRUE)
  }
  if(length(queue) > 0) {
    reqFifoPath = queue[1]
    queue = queue[-1]
  } else {
    Sys.sleep(0.01)
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
  reqPayloadPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".reqrd")
  if(file.exists(reqPayloadPath)) {
    load(reqPayloadPath, envir=env)
    unlink(reqPayloadPath)
  }

  result = NULL
  result = tryCatch(eval(request$expr, envir=env), error = function(ex) {})
  rm(env)

  resPayloadPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".resrd")
  save(result, file=resPayloadPath)

  #response fifo
  respFifoPayload = list(
    result=0
  )
  resFifoPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".resfifo")
  serializedPayload = serialize(respFifoPayload, NULL, ascii=T)
  serializedPayload = rawToChar(serializedPayload)

  con = fifo(resFifoPath, open="wt", blocking=T)
  writeLines(serializedPayload, con)
  close(con)
}

concerto.log("listener service closing")