getTemplateParams = function(templateParams) {
  if(!is.list(templateParams)) {
    templateParams = list()
  }
  for(.name in .dynamicInputs) {
    templateParams[[.name]] = get(.name)
  }
  return(templateParams)
}

numericTimeLimit = as.numeric(timeLimit)
response = concerto5:::concerto.template.show(
  template=template, 
  html=if(!is.null(html) && !is.na(html) && html != "") {html} else {""},
  params=getTemplateParams(templateParams), 
  timeLimit=if(!is.na(numericTimeLimit)) { numericTimeLimit } else { 0 }
)

for(.name in .dynamicReturns) {
  assign(.name, response[[.name]])
}
if(length(.dynamicBranches) > 0) {
  .branch = response$buttonPressed
}
