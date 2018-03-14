concerto.template.join = function(html="",css="",js="",templateId=NULL,params=list()){
  if(!is.list(params)) stop("'params' must be a list!")
  if(!is.null(templateId)) {
    template = concerto.template.get(templateId)
    if(is.null(template)) stop(paste("Template #",templateId," not found!",sep=''))
    html = template$html
    css = template$css
    js = template$js
  }
  result = ""
  if(css != "") {
    result = paste0("<style>",concerto.template.insertParams(css,params,F),"</style>")
  }
  if(js != "") {
    result = paste0(result, "<script>",concerto.template.insertParams(js,params,F),"</script>")
  }
  result = paste0(result, concerto.template.insertParams(html,params,F))
  return(result)
}
