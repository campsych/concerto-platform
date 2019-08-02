if(!is.list(params)) {
  params = list()
}
for(.name in .dynamicInputs) {
  params[[.name]] = get(.name)
}

html = concerto.template.join(templateId=layout)
content = fromJSON(content)
if(length(content) > 0) {
  for(i in 1:length(content)) {
    pageParam = list()
    pageParam[[content[[i]]$name]] = concerto.template.join(templateId=content[[i]]$template)
    html = concerto.template.insertParams(html, pageParam, removeMissing=F)
  }
}

html = concerto.template.insertParams(html, params, removeMissing=F)
