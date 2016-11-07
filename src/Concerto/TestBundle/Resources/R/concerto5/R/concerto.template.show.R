concerto.template.show = function(
  templateId=-1, 
  html="", 
  head="", 
  params=list(),
  timeLimit=0,
  finalize=F) {
    if(!is.list(params)) stop("'params' must be a list!")
  
    if(templateId==-1 && html=="") stop("templateId or html must be declared")
   
    if(html!=""){
      concerto$session$templateHead <<- concerto.template.insertParams(head,params)
      concerto$session$templateHtml <<- concerto.template.insertParams(html,params)
    } else {
      template <- concerto.template.get(templateId)
      if(dim(template)[1]==0) stop(paste("Template #",templateId," not found!",sep=''))
      concerto$session$templateHead <<- concerto.template.insertParams(template$head,params)
      concerto$session$templateHtml <<- concerto.template.insertParams(template$html,params)
      concerto$session$template_id <<- template$id
    }
    concerto$session$timeLimit <<- timeLimit

    for(name in ls(params)) {
        concerto$templateParams[[name]] <<- params[[name]]
    }
    concerto$session$templateParams <<- toJSON(concerto$templateParams)

    if(finalize) {
        concerto$session$status <<- STATUS_FINALIZED
    } else {
        concerto5:::concerto.session.update()
        concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE)

        concerto$templateParams <<- list()

        resp = concerto5:::concerto.server.listen()
        return(resp)
    }
}
