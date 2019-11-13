concerto.event.remove = function(name, fun){
    indicesToRemove = c()
    if(length(concerto$events[[name]]) > 0) {
        i = 0
        for(currentFun in concerto$events[[name]]) {
            i = i + 1
            if(identical(currentFun, fun)) {
                indicesToRemove = c(indicesToRemove, i)
            }
        }
    }

    if(length(indicesToRemove) > 0) {
        concerto$events[[name]] <<- concerto$events[[name]][-indicesToRemove]
    }
}
