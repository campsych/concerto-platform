library(rjson)
library(tools)
for(package in sort(.packages(T))){
    library(package,character.only=T)
    db <- Rd_db(package)
    adm <- c()

    for(doc in db){
        outfile <- tempfile(fileext = ".html")
        tools::Rd2HTML(doc,out=outfile)
        HTML <- paste0(readLines(outfile), collapse="")
        unlink(outfile)

        aliases <- tools:::.Rd_get_metadata(x=doc,kind='alias')
        for(alias in aliases) {
            if(!grepl('^[a-zA-Z0-9_.]*$',alias,perl=T) || alias %in% adm || !exists(alias) || !is.function(get(alias))){
                next
            }
            adm <- c(adm,alias)
            form <- formals( alias ) 
            arguments <- names( form )
            defaults <- paste( form )

            json = rjson::toJSON(list(lib=package,fun=alias,doc=HTML,args=arguments,defs=defaults))
            print(json,quote=F)
        }
        doc <- NULL
    }
}