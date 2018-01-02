concerto.list.merge = function(list1,list2){
    if(!is.list(list1)) stop("'list1' must be a list!")
    if(!is.list(list2)) stop("'list2' must be a list!")

    res = list1
    for(key in ls(list2)) {
        res[[key]] = list2[[key]]
    }
    return(res)
}
