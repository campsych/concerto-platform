concerto.template.show = function(
  templateId=-1, 
  html="", 
  head="", 
  params=list(),
  timeLimit=0,
  finalize=F,
  removeMissingParams=T,
  bgWorkers=list()) {
    if(!is.null(concerto$queuedResponse)) {
        response = concerto$queuedResponse
        concerto$queuedResponse <<- NULL
        return(response)
    }

    if(!is.list(params)) stop("'params' must be a list!")
    if(templateId==-1 && html=="") stop("templateId or html must be declared")
   
    if(html!=""){
      concerto$response$templateHead <<- concerto.template.insertParams(head,params,removeMissing=removeMissingParams)
      concerto$response$templateHtml <<- concerto.template.insertParams(html,params,removeMissing=removeMissingParams)
      concerto$response$templateCss <<- ""
      concerto$response$templateJs <<- ""
    } else {
      template <- concerto.template.get(templateId)
      if(is.null(template)) stop(paste("Template #",templateId," not found!",sep=''))
      concerto$response$templateHead <<- concerto.template.insertParams(template$head,params,removeMissing=removeMissingParams)
      concerto$response$templateCss <<- concerto.template.insertParams(template$css,params,removeMissing=removeMissingParams)
      concerto$response$templateJs <<- concerto.template.insertParams(template$js,params,removeMissing=removeMissingParams)
      concerto$response$templateHtml <<- concerto.template.insertParams(template$html,params,removeMissing=removeMissingParams)
    }
    concerto$session$timeLimit <<- timeLimit
    concerto$bgWorkers <<- bgWorkers

    if(length(params) > 0) {
        for(name in ls(params)) {
            if(is.null(params[[name]])) {
                concerto$templateParams[name] <<- list(NULL)
            } else {
                concerto$templateParams[[name]] <<- params[[name]]
            }
        }
    }

    if(exists("concerto.onBeforeTemplateShow")) {
        do.call("concerto.onBeforeTemplateShow",list(params=concerto$templateParams), envir = .GlobalEnv)
    }

    data = concerto$response
    data$templateParams = concerto$templateParams
    if(finalize) {
        concerto5:::concerto.session.stop(STATUS_FINALIZED, RESPONSE_VIEW_FINAL_TEMPLATE, data)
    } else {
        concerto5:::concerto.session.update()
        concerto$templateParams <<- list()
        concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE, data)
        concerto$response <<- list()

        if(concerto$runnerType == RUNNER_SERIALIZED) {
            concerto5:::concerto.session.serialize()
            concerto5:::concerto.session.stop(STATUS_RUNNING)
        }

        return(concerto5:::concerto.server.listen())
    }
}
