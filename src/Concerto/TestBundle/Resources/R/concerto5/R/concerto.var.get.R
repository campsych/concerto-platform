concerto.var.get = c.get = function(name, global=F){
    if(global) {
        return(concerto$globals[[name]])
    } else {
        flowIndex = length(concerto$flow)
        return(concerto$flow[[flowIndex]]$globals[[name]])
    }
}