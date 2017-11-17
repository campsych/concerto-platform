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

  returns <<- list()
  tryCatch({
        setwd(concerto$workingDir)

        returns <<- concerto.test.run(concerto$flow[[1]]$id, mainTest=TRUE, ongoingResumeFlowIndex=1)

  }, error = function(e) {
        if(concerto$session$status == STATUS_RUNNING){
          concerto.log(e)
          response = RESPONSE_ERROR
          if(e$message == "session unresumable") {
            response = RESPONSE_UNRESUMABLE
          }
          concerto5:::concerto.server.respond(response)
          concerto$session$error <<- e
          concerto$session$status <<- STATUS_ERROR
          concerto5:::concerto.session.update()
          stop("Error executing test logic.")
        }
  })

  if(concerto$session$status == STATUS_FINALIZED){
        concerto5:::concerto.session.finalize(RESPONSE_VIEW_FINAL_TEMPLATE, returns)
  } else if(concerto$session$status == STATUS_RUNNING){
        concerto5:::concerto.session.finalize(RESPONSE_FINISHED, returns)
  }
}
