concerto.event.fire = function(name, args){
    concerto.log(name, "event fire")
    for(fun in concerto$events[[name]]) {
        do.call(fun, args, envir = .GlobalEnv)
    }
}