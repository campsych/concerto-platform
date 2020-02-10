concerto.worker.getTemplate = function(response) {
    template <- concerto.template.get(response$templateId)
    if (is.null(template)) return(NA)

    concerto.log(template)

    content = list(
        head=concerto.template.insertParams(template$head, response$params),
        css=concerto.template.insertParams(template$css, response$params),
        js=concerto.template.insertParams(template$js, response$params),
        html=concerto.template.insertParams(template$html, response$params)
    )
    return(content)
}