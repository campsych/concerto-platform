getTemplateParams = function() {
  if(!is.list(templateParams)) {
    templateParams = list()
  }

  templateParams$logo = logo
  templateParams$title = title
  templateParams$content = content
  templateParams$buttonLabel = buttonLabel
  templateParams$footer = footer

  for(.name in .dynamicInputs) {
    templateParams[[.name]] = get(.name)
  }
  return(templateParams)
}

numericTimeLimit = as.numeric(timeLimit)
if(!is.list(cookies)) {
  cookies = list()
}
if(!is.list(bgWorkers)) {
  bgWorkers = list()
}
response = concerto5:::concerto.template.show(
  template=template, 
  html=if(!is.null(html) && !is.na(html) && html != "") {html} else {""},
  params=getTemplateParams(), 
  timeLimit=if(!is.na(numericTimeLimit)) { numericTimeLimit } else { 0 },
  cookies=cookies,
  bgWorkers=bgWorkers
)

cookies = concerto$lastResponse$cookies

for(.name in .dynamicReturns) {
  if(!is.null(response[[.name]])) {
    assign(.name, response[[.name]])
  }
}
if(length(.dynamicBranches) > 0) {
  .branch = response$buttonPressed
}
