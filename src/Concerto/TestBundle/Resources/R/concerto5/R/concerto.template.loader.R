concerto.template.loader <-
function(
    templateId=-1, 
    html="", 
    head="", 
    params=list()){
  print(paste("setting loader template #",workspaceID,":",templateId,"...",sep=''))

  if(templateId==0){
    concerto$session$loaderHtml <<- ""
    return
  }
  if(templateId==-1 && html=="") stop("templateId or HTML must be declared")
  if(!is.list(params)) stop("'params' must be a list!")
  
  template <- concerto.template.get(templateId)

  if(html!=""){
    concerto$session$loaderHead <<- concerto.template.insertParams(head,params)
    concerto$session$loaderHtml <<- concerto.template.insertParams(html,params)
  } else {
    if(dim(template)[1]==0) stop(paste("Template #",workspaceID,":",templateId," not found!",sep=''))
    concerto$session$loaderHead <<- concerto.template.insertParams(template$head,params)
    concerto$session$loaderHtml <<- concerto.template.insertParams(template$html,params)
  }
  concerto$session$loader_id <<- template$id
}
