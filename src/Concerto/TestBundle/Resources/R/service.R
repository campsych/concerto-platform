ENV_CONCERTO_R_SERVICE_FIFO_PATH = Sys.getenv("CONCERTO_R_SERVICE_FIFO_PATH")
ENV_CONCERTO_R_FORCED_GC_INTERVAL = as.numeric(Sys.getenv("CONCERTO_R_FORCED_GC_INTERVAL"))

concerto.log("starting service listener")

#unlink(paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, "*"))
lastForcedGcTime = as.numeric(Sys.time())
repeat {
  if(ENV_CONCERTO_R_FORCED_GC_INTERVAL >= 0) {
    currentTime = as.numeric(Sys.time())
    if(currentTime - lastForcedGcTime > ENV_CONCERTO_R_FORCED_GC_INTERVAL) {
      gcOutput = gc(F)
      lastForcedGcTime = currentTime
    }
  }

  queue = list.files(ENV_CONCERTO_R_SERVICE_FIFO_PATH, pattern=".*\\.reqfifo$", full.names=TRUE)

  fl = NULL
  lockPath = NULL
  reqFifoPath = NULL
  locked = F
  if(length(queue) > 0) {
    for(path in queue) {
      reqFifoPath = path
      lockPath = paste0(reqFifoPath, ".lock")
      fl = lock(lockPath, exclusive=T, timeout=0)
      locked = !is.null(fl)
      if(locked) {
        if(!file.exists(path)) {
          locked = F
          unlock(fl)
          unlink(lockPath)
          next
        }
        break
      }
    }
  }

  if(!locked) {
    Sys.sleep(0.25)
    next
  }

  con = fifo(reqFifoPath, open="rt", blocking=T)
  request = readLines(con)
  close(con)
  unlink(reqFifoPath)
  unlock(fl)
  unlink(lockPath)

  request = paste0(request, collapse="\n")
  request = charToRaw(request)
  request = unserialize(request)

  env = new.env()
  for(name in ls(.GlobalEnv)) {
    assign(name, .GlobalEnv[[name]], env)
  }
  reqPayloadPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".reqrd")
  if(file.exists(reqPayloadPath)) {
    load(reqPayloadPath, envir=env)
    unlink(reqPayloadPath)
  }

  result = tryCatch({
    list(
      success=T,
      data=eval(request$expr, envir=env)
    )
  }, error = function(ex) {
    list(
      success=F,
      errorMessage=ex$message
    )
  })
  rm(env)

  if(result$success) {
    resPayloadPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".resrd")
    resultData = result$data
    save(resultData, file=resPayloadPath)
  }

  #response JSON
  respJsonPayload = toJSON(list(
    success=result$success,
    errorMessage=result$errorMessage
  ))
  resJsonPath = paste0(ENV_CONCERTO_R_SERVICE_FIFO_PATH, request$sessionHash, "_", request$requestId, ".resjson")

  con = file(resJsonPath, open="wt", blocking=F)
  writeLines(respJsonPayload, con)
  close(con)
}

concerto.log("listener service closing")