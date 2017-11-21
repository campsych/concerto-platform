concerto.template.show = function(
  templateId=-1, 
  html="", 
  head="", 
  params=list(),
  timeLimit=0,
  finalize=F,
  removeMissingParams=T) {
    if(!is.list(params)) stop("'params' must be a list!")
  
    if(templateId==-1 && html=="") stop("templateId or html must be declared")
   
    if(html!=""){
      concerto$session$templateHead <<- concerto.template.insertParams(head,params,removeMissing=removeMissingParams)
      concerto$session$templateHtml <<- concerto.template.insertParams(html,params,removeMissing=removeMissingParams)
      concerto$session$templateCss <<- ""
      concerto$session$templateJs <<- ""
    } else {
      template <- concerto.template.get(templateId)
      if(dim(template)[1]==0) stop(paste("Template #",templateId," not found!",sep=''))
      concerto$session$templateHead <<- concerto.template.insertParams(template$head,params,removeMissing=removeMissingParams)
      concerto$session$templateCss <<- concerto.template.insertParams(template$css,params,removeMissing=removeMissingParams)
      concerto$session$templateJs <<- concerto.template.insertParams(template$js,params,removeMissing=removeMissingParams)
      concerto$session$templateHtml <<- concerto.template.insertParams(template$html,params,removeMissing=removeMissingParams)
      concerto$session$template_id <<- template$id
    }
    concerto$session$timeLimit <<- timeLimit

    if(length(params) > 0) {
        for(name in ls(params)) {
            concerto$templateParams[[name]] <<- params[[name]]
        }
    }
    concerto$session$templateParams <<- toJSON(concerto$templateParams)

    if(exists("concerto.onBeforeTemplateShow")) {
        do.call("concerto.onBeforeTemplateShow",list(params=concerto$templateParams), envir = .GlobalEnv)
    }

    if(finalize) {
        concerto$session$status <<- STATUS_FINALIZED
    } else {
        concerto5:::concerto.session.update()
        concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE)

        concerto$templateParams <<- list()

        resp = concerto5:::concerto.server.listen()

        if(exists("concerto.onTemplateSubmit")) {
            do.call("concerto.onTemplateSubmit",list(response=resp), envir = .GlobalEnv)
        }

        return(resp)
    }
}
