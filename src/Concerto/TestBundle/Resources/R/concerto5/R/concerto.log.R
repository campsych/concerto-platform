concerto.log = function(obj, title=""){
    print(paste0("[",Sys.time(),if(title!=""){paste0(" - ",title)},"]:"))
    print(obj)
}