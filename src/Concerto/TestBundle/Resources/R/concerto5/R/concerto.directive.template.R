concerto.directive.template = function(args, params){
    args = strsplit(args, ",")[[1]]
    name = args[1]
    opts = list()
    if (length(args) > 1) {
        for(i in 2:length(args)) {
            opt = strsplit(trimws(args[i]), "=")[[1]]
            opts[[opt[1]]] = opt[2]
        }
    }

    hideHtml = !is.null(opts$html) && as.logical(opts$html) == F
    hideCss = !is.null(opts$css) && as.logical(opts$css) == F
    hideJs = !is.null(opts$js) && as.logical(opts$js) == F

    insert = concerto.template.join(
        templateId = name,
        params = params,
        html = if(hideHtml) NULL else '',
        css = if(hideCss) NULL else '',
        js = if(hideJs) NULL else ''
    )

    if (Sys.info()['sysname'] == "Windows") {
        if (Encoding(insert) == "UTF-8") { insert = enc2native(insert)}
    }
    return(insert)
}