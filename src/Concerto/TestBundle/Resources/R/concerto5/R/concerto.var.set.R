concerto.var.set = c.set = function(name, value, global=F){
    if(global) {
        concerto$globals[[name]] <<- value
    } else {
        flowIndex = length(concerto$flow)
        concerto$flow[[flowIndex]]$globals[[name]] <<- value
    }
    return(value)
}
