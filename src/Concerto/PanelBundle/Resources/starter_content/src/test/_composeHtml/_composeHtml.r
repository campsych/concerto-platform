if(!is.list(params)) {
  params = list()
}
for(.name in .dynamicInputs) {
  params[[.name]] = get(.name)
}

html = concerto.template.join(
  templateId=layout,
  html=layoutHtml
)
content = fromJSON(content)
if(length(content) > 0) {
  for(i in 1:length(content)) {
    params[[content[[i]]$name]] = concerto.template.join(
      templateId=content[[i]]$template,
      html=content[[i]]$html
    )
  }
}

html = concerto.template.insertParams(html, params, removeMissing=F)