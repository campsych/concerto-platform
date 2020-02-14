concerto.var.set = c.set = function(name, value, global=F, flowIndexOffset = 0, posOffset = 0, flowIndex = NULL){
    if(posOffset != 0) {
        flowIndexOffset = posOffset
        concerto.log("c.set : posOffset argument is deprecated. Use flowIndexOffset argument instead")
    }

    if(global || (concerto$flowIndex == 0 && is.null(flowIndex))) {
        concerto$globals[name] <<- list(value)
    } else {
        if(is.null(flowIndex)) {
            flowIndex = concerto$flowIndex
        }
        concerto$flow[[flowIndex + flowIndexOffset]]$globals[name] <<- list(value)
    }
    return(value)
}
