concerto.service.eval = function(expr, params = list()) {
  if(!is.expression(expr)) stop("expr must be an expression")
  if(!is.list(params)) stop("params must be a list")

  concerto$serviceRequestId <<- concerto$serviceRequestId + 1
  requestId = concerto$serviceRequestId

  payload = list(
    expr = expr,
    params = params,
    requestId = requestId,
    sessionHash = concerto$session$hash
  )
  serializedPayload = serialize(payload, NULL, ascii=T)
  serializedPayload = rawToChar(serializedPayload, T)

  reqFifoPath = paste0(concerto$serviceFifoDir, payload$sessionHash, "_", payload$requestId, ".reqfifo")
  con = fifo(reqFifoPath, open="wt", blocking=T)
  writeLines(serializedPayload, con)
  close(con)

  resFifoPath = paste0(concerto$serviceFifoDir, payload$sessionHash, "_", payload$requestId, ".resfifo")
  repeat {
    if(file.exists(resFifoPath)) {
      con = fifo(resFifoPath, open="rt")
      response = readLines(con)
      close(con)
      unlink(resFifoPath)

      response = paste0(response, collapse="\n")
      response = charToRaw(response)
      response = unserialize(response)
      return(response$result)
    } else {
      Sys.sleep(0.01)
    }
  }
}