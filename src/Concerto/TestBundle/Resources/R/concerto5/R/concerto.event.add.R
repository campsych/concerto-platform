concerto.event.add = function(name, fun){
    concerto$events[[name]] <<- c(concerto$events[[name]], fun)
}
