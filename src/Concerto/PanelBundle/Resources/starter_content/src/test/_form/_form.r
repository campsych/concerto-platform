getTemplateParams = function(templateParams, fields, initialValues) {
  if(!is.list(templateParams)) {
    templateParams = list()
  }
  for(name in .dynamicInputs) {
    templateParams[[name]] = get(name)
  }
  templateParams$fields = fields
  templateParams$initialValues = initialValues
  return(templateParams)
}

response = concerto5:::concerto.template.show(
  template,
  params=getTemplateParams(templateParams, fields, initialValues)
)

for(.name in .dynamicReturns) {
  assign(.name, response[[.name]])
}
if(".branch" %in% .dynamicReturns) {
  .branch = response$buttonPressed
  if(!(.branch %in% .dynamicBranches)) { .branch = "out" }
}
