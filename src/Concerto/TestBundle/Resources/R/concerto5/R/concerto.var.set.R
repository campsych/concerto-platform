concerto.var.set = c.set = function(name, value, global=F, posOffset = 0){
    if(global || concerto$flowIndex == 0) {
        concerto$globals[name] <<- list(value)
    } else {
        flowIndex = concerto$flowIndex
        concerto$flow[[flowIndex + posOffset]]$globals[name] <<- list(value)
    }
    return(value)
}
