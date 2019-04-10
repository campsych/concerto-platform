concerto.template.isResponseQueued = function(){
     return(concerto$runnerType == RUNNER_SERIALIZED && !is.null(concerto$queuedResponse))
}
