if(!is.list(params)) {
  params = list()
}
for(.name in .dynamicInputs) {
  params[[.name]] = get(.name)
}

if(!is.na(loaderTemplate)) {
  concerto.template.loader(
    templateId=loaderTemplate, 
    html=loaderTemplateHtml,
    params=params
  )
}
