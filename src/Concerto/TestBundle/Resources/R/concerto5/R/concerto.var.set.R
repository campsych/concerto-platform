concerto.var.set = c.set = function(name, value){
    concerto$globals[[name]] <<- value
    return(value)
}
