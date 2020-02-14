concerto.var.get = c.get = function(name, global=F, all=F, flowIndexOffset = 0, posOffset = 0, flowIndex = NULL){
    if(posOffset != 0) {
        flowIndexOffset = posOffset
        concerto.log("c.get : posOffset argument is deprecated. Use flowIndexOffset argument instead")
    }

    if(global || (concerto$flowIndex == 0 && is.null(flowIndex))) {
        if(all) { return(concerto$globals) }
        else return(concerto$globals[[name]])
    } else {
        if(is.null(flowIndex)) {
            flowIndex = concerto$flowIndex
        }
        if(all) { return(concerto$flow[[flowIndex + flowIndexOffset]]$globals) }
        else return(concerto$flow[[flowIndex + flowIndexOffset]]$globals[[name]])
    }
}