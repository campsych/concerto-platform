if(!is.list(params)) {
  params = list()
}
for(.name in .dynamicInputs) {
  params[[.name]] = get(.name)
}

if(is.na(loaderTemplate) || loaderTemplate == "") { loaderTemplate = -1 }

concerto.template.loader(
  templateId=loaderTemplate, 
  html=loaderTemplateHtml,
  params=params
)