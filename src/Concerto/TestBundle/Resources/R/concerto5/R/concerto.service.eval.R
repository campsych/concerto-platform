concerto.service.eval = function(expr, params = list()) {
  if(!is.expression(expr)) stop("expr must be an expression")
  if(!is.list(params)) stop("params must be a list")

  currentTimestamp = as.numeric(Sys.time())
  concerto$serviceRequestId <<- concerto$serviceRequestId + 1
  requestId = concerto$serviceRequestId

  #request payload rd
  if(length(params) > 0) {
    env = new.env()
    for(name in ls(params)) {
      assign(name, params[[name]], envir=env)
    }

    reqPayloadPath = paste0(concerto$serviceFifoDir, concerto$session$hash, "_", requestId, ".reqrd")
    save(list=ls(params), file=reqPayloadPath, envir=env)
    rm(env)
  }

  #request fifo
  fifoPayload = list(
    expr = expr,
    requestId = requestId,
    sessionHash = concerto$session$hash
  )
  serializedPayload = serialize(fifoPayload, NULL, ascii=T)
  serializedPayload = rawToChar(serializedPayload)

  reqFifoPath = paste0(concerto$serviceFifoDir, currentTimestamp, "_", concerto$session$hash, "_", requestId, ".reqfifo")
  con = fifo(reqFifoPath, open="wt", blocking=T)
  writeLines(serializedPayload, con)
  close(con)

  resJsonPath = paste0(concerto$serviceFifoDir, concerto$session$hash, "_", requestId, ".resjson")
  resPayloadPath = paste0(concerto$serviceFifoDir, concerto$session$hash, "_", requestId, ".resrd")
  repeat {
    if(file.exists(resJsonPath)) {
      #response JSON
      con = file(resJsonPath, open="rt", blocking=F)
      response = readLines(con)
      close(con)

      if(length(response) == 0) {
        Sys.sleep(0.1)
        next
      }

      unlink(resJsonPath)
      response = fromJSON(response)

      if(response$success) {
        #response payload rd
        load(resPayloadPath)
        unlink(resPayloadPath)
        response$result = resultData
      }
      return(response)
    } else {
      Sys.sleep(0.1)
    }
  }
}