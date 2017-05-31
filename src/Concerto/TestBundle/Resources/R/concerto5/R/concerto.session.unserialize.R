concerto.session.unserialize <- function(){
  print("unserializing session...")
  
  temp_state <- concerto
  if(!file.exists(concerto$sessionFile)) {
    stop("session unresumable")
  }
  restore.session(concerto$sessionFile)
  concerto <<- temp_state
  
  unlink(concerto$sessionFile)
  if(exists("concerto.onUnserialize")) {
    do.call("concerto.onUnserialize",list(lastSubmit=fromJSON(commandArgs(TRUE)[10])), envir = .GlobalEnv)
  } else {
    print("concerto.onUnserialize = function(lastSubmit) is missing!")
    stop("session unresumable")
  }
  print("session unserialized")
}
