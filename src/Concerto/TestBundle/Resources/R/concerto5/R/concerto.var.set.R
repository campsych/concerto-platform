concerto.var.set = function(name, value){
    concerto$promoted[[name]] <<- value
    return(value)
}
