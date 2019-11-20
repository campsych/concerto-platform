concerto.var.get = c.get = function(name, global=F, all=F, posOffset = 0){
    if(global || concerto$flowIndex == 0) {
        if(all) { return(concerto$globals) }
        else return(concerto$globals[[name]])
    } else {
        flowIndex = concerto$flowIndex
        if(all) { return(concerto$flow[[flowIndex + posOffset]]$globals) }
        else return(concerto$flow[[flowIndex + posOffset]]$globals[[name]])
    }
}