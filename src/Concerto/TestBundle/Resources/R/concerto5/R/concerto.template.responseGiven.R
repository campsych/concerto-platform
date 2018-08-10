concerto.template.responseGiven = function(){
     return(concerto$runnerType == RUNNER_SERIALIZED && !is.null(concerto$queuedResponse))
}
