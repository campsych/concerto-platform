concerto.var.set = c.set = function(name, value, global=F){
    if(global || length(concerto$flow) == 0) {
        if(is.null(value)) {
            concerto$globals[name] <<- list(NULL)
        } else {
            concerto$globals[[name]] <<- value
        }
    } else {
        flowIndex = length(concerto$flow)
        if(is.null(value)) {
            concerto$flow[[flowIndex]]$globals[name] <<- list(NULL)
        } else {
            concerto$flow[[flowIndex]]$globals[[name]] <<- value
        }
    }
    return(value)
}
