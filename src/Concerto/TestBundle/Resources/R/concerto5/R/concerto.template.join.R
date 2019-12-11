concerto.template.join = function(html="", css="", js="", templateId=NULL, params=list()){
    if (! is.list(params)) stop("'params' must be a list!")
    if (! is.null(templateId) && (is.null(html) || html == "")) {
        template = concerto.template.get(templateId)
        if (is.null(template)) stop(paste("Template #", templateId, " not found!", sep = ''))
        if (! is.null(html)) { html = template$html}
        if (! is.null(css)) { css = template$css}
        if (! is.null(js)) { js = template$js}
    }
    result = ""
    if (! is.null(css) && css != "") {
        result = paste0("<style>", concerto.template.insertParams(css, params, F), "</style>")
    }
    if (! is.null(html)) {
        result = paste0(result, concerto.template.insertParams(html, params, F))
    }
    if (! is.null(js) && js != "") {
        result = paste0(result, "<script>", concerto.template.insertParams(js, params, F), "</script>")
    }
    return(result)
}
