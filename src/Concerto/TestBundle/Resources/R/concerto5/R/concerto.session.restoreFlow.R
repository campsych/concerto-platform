concerto.session.restoreFlow <- function(sessionHash){
  concerto.log("restore flow session...")

  file = gsub(concerto$session$hash, sessionHash, concerto$sessionFile)
  
  old_c = concerto
  if(!file.exists(file)) {
    stop("session file not found!")
  }
  restore.session(file)
  new_c = concerto
  concerto <<- old_c
  concerto$flow <<- new_c$flow
  concerto$promoted <<- new_c$promoted

  unlink(file)

  concerto.log("flow session restored")

  if(length(concerto$flow) == 0) {
    stop("no flow stack!")
  }
  if(concerto$flow[[1]]$type != 2) {
    stop("top most test on stack is not flow based!")
  }

  tryCatch({
        setwd(concerto$workingDir)
        concerto.test.run(concerto$flow[[1]]$id, params=concerto$flow[[1]]$params, mainTest=TRUE, ongoingResumeFlowIndex=1)

        concerto5:::concerto.session.stop(STATUS_FINALIZED, RESPONSE_FINISHED)
  }, error = function(e) {
        concerto.log(e)
        concerto$session$error <<- e
        concerto5:::concerto.session.stop(STATUS_ERROR, RESPONSE_ERROR)
  })
}
