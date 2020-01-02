getTemplateParams = function() {
  if(!is.list(templateParams)) {
    templateParams = list()
  }
  templateParams$fields = fields
  templateParams$initialValues = initialValues
  templateParams$title = title
  templateParams$logo = logo
  templateParams$instructions = instructions
  templateParams$buttonLabel = buttonLabel
  templateParams$footer = footer

  for(name in .dynamicInputs) {
    templateParams[[name]] = get(name)
  }
  return(templateParams)
}

response = concerto5:::concerto.template.show(
  templateId=template,
  html=templateHtml,
  params=getTemplateParams()
)

for(.name in .dynamicReturns) {
  if(!is.null(response[[.name]])) {
    assign(.name, response[[.name]])
  }
}
if(".branch" %in% .dynamicReturns) {
  .branch = response$buttonPressed
  if(!(.branch %in% .dynamicBranches)) { .branch = "out" }
}
