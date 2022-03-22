concerto.template.show = function(
    templateId=-1,
    html="",
    head="",
    params=list(),
    timeLimit=0,
    finalize=F,
    removeMissingParams=T,
    bgWorkers=list(),
    skipOnResume=F,
    cookies=list(),
    protectedFilesAccess=F,
    sessionFilesAccess=F
) {
    concerto$skipTemplateOnResume <<- skipOnResume
    if (! is.null(concerto$queuedResponse)) {
        response = concerto$queuedResponse
        concerto$queuedResponse <<- NULL
        return(response)
    }

    if (! is.list(params)) stop("'params' must be a list!")
    if (templateId == -1 && html == "") stop("templateId or html must be declared")

    params = concerto.template.makeParams(params)

    concerto$response$protectedFilesAccess <<- protectedFilesAccess
    concerto$response$sessionFilesAccess <<- sessionFilesAccess
    if (html != "") {
        concerto$response$templateHead <<- concerto.template.insertParams(head, params, removeMissing = removeMissingParams)
        concerto$response$templateHtml <<- concerto.template.insertParams(html, params, removeMissing = removeMissingParams)
        concerto$response$templateCss <<- ""
        concerto$response$templateJs <<- ""
    } else {
        template <- concerto.template.get(templateId)
        if (is.null(template)) stop(paste("Template #", templateId, " not found!", sep = ''))
        concerto$response$templateHead <<- concerto.template.insertParams(template$head, params, removeMissing = removeMissingParams)
        concerto$response$templateCss <<- concerto.template.insertParams(template$css, params, removeMissing = removeMissingParams)
        concerto$response$templateJs <<- concerto.template.insertParams(template$js, params, removeMissing = removeMissingParams)
        concerto$response$templateHtml <<- concerto.template.insertParams(template$html, params, removeMissing = removeMissingParams)
    }
    concerto$session$timeLimit <<- timeLimit

    workers = list(
        getTemplate = concerto.worker.getTemplate
    )
    for(name in ls(bgWorkers)) {
        workers[[name]] = bgWorkers[[name]]
    }
    concerto$bgWorkers <<- workers

    concerto$templateParams <<- params

    concerto.event.fire("onBeforeTemplateShow", list(params = concerto$templateParams))

    data = concerto$response
    data$templateParams = concerto$templateParams
    data$cookies = cookies
    if(!is.null(concerto$lastResponse$values$submitId)) {
        data$lastSubmitId = as.numeric(concerto$lastResponse$values$submitId)
    }
    if (finalize) {
        concerto5:::concerto.session.stop(STATUS_FINALIZED, RESPONSE_VIEW_FINAL_TEMPLATE, data)
    } else {
        repeat {
            concerto5:::concerto.session.update()
            concerto$templateParams <<- list()

            concerto$lastSubmitResult <<- data
            concerto$lastSubmitId <<- data$lastSubmitId

            if (concerto$runnerType == RUNNER_SERIALIZED) {
                concerto5:::concerto.session.serialize()
            }

            concerto5:::concerto.server.respond(RESPONSE_VIEW_TEMPLATE, data)
            concerto$response <<- list()

            if (concerto$runnerType == RUNNER_SERIALIZED) {
                concerto5:::concerto.session.stop(STATUS_RUNNING)
            }

            response = concerto5:::concerto.server.listen(skipOnResume)
            if(!is.null(response)) return(response)
        }
    }
}
