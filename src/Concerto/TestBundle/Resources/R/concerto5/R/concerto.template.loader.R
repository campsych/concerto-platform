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
    concerto$response$loaderHead <<- concerto.template.insertParams(head,params)
    concerto$response$loaderHtml <<- concerto.template.insertParams(html,params)
    concerto$response$loaderCss <<- ""
    concerto$response$loaderJs <<- ""
  } else {
    if(is.null(template)) stop(paste("Template #",templateId," not found!",sep=''))
    concerto$response$loaderHead <<- concerto.template.insertParams(template$head,params)
    concerto$response$loaderCss <<- concerto.template.insertParams(template$css,params)
    concerto$response$loaderJs <<- concerto.template.insertParams(template$js,params)
    concerto$response$loaderHtml <<- concerto.template.insertParams(template$html,params)
  }

  for(name in ls(params)) {
    if(is.null(params[[name]])) {
      concerto$templateParams[name] <<- list(NULL)
    } else {
      concerto$templateParams[[name]] <<- params[[name]]
    }
  }
}
