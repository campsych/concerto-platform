concerto.directive.trans = function(opts, params){
    insert = c.trans(opts)
    if(Sys.info()['sysname'] == "Windows") {
        if(Encoding(insert) == "UTF-8") { insert = enc2native(insert) }
    }
    return(insert)
}