concerto.var.get = c.get = function(name, global=F, all=F){
    if(global || length(concerto$flow) == 0) {
        if(all) { return(concerto$globals) }
        else return(concerto$globals[[name]])
    } else {
        flowIndex = length(concerto$flow)
        if(all) { return(concerto$flow[[flowIndex]]$globals) }
        else return(concerto$flow[[flowIndex]]$globals[[name]])
    }
}