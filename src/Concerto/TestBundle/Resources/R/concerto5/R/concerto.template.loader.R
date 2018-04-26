concerto.template.loader <-
function(
    templateId=-1, 
    html="", 
    head="", 
    params=list()){
  if(!is.list(params)) stop("'params' must be a list!")
  if(templateId==-1 && html=="") stop("templateId or html must be declared")
  
  template <- concerto.template.get(templateId)

  if(html!=""){
    concerto$session$loaderHead <<- concerto.template.insertParams(head,params)
    concerto$session$loaderHtml <<- concerto.template.insertParams(html,params)
  } else {
    if(is.null(template)) stop(paste("Template #",templateId," not found!",sep=''))
    concerto$session$loaderHead <<- concerto.template.insertParams(template$head,params)
    concerto$session$loaderCss <<- concerto.template.insertParams(template$css,params)
    concerto$session$loaderJs <<- concerto.template.insertParams(template$js,params)
    concerto$session$loaderHtml <<- concerto.template.insertParams(template$html,params)

  }
  concerto$session$loader_id <<- template$id

  for(name in ls(params)) {
    concerto$templateParams[[name]] <<- params[[name]]
  }
}
