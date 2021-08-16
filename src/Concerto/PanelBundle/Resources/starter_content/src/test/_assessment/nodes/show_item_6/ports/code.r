getTemplateParams = function() {
  canGoBack = if(settings$order != "cat" && settings$canGoBack == "1" && page > 1) { 1 } else { 0 }
  maxItems = dim(items)[1]
  itemNumLimit = as.numeric(settings$itemNumLimit)
  if(itemNumLimit > 0) { maxItems = min(maxItems, itemNumLimit) }
  totalPages = ceiling(maxItems / as.numeric(settings$itemsPerPage))
  
  processedItems = toJSON(data.frame(itemsSafe))
  processedItems = concerto.template.insertParams(processedItems, templateParams, F)
  templateParams$items = fromJSON(processedItems)
  
  templateParams$responseRequired = as.numeric(settings$responseRequired)
  templateParams$responseRequiredAlert = settings$responseRequiredAlert
  templateParams$footer = settings$footer
  templateParams$logo = settings$logo
  templateParams$nextButtonLabel = settings$nextButtonLabel
  templateParams$title = settings$title

  templateParams$canGoBack = canGoBack
  templateParams$responses = responsesSafe
  templateParams$backButtonLabel = settings$backButtonLabel
  templateParams$showPageInfo = as.numeric(settings$showPageInfo)
  if(settings$order != "cat" || settings$minAccuracy == 0) {
    templateParams$page = page
    templateParams$totalPages = totalPages
  }

  return(templateParams)
}

getTestTimeLeft = function(oneSecRound=T) {
  testTimeLimit = as.numeric(settings$testTimeLimit) + as.numeric(settings$testTimeLimitOffset)
  testTimeLimitType = settings$testTimeLimitType
  limit = 0
  if(testTimeLimit > 0) {
    if(testTimeLimitType == "startedAgo") {
      startedAgo = as.numeric(Sys.time()) - testTimeStarted
      limit = testTimeLimit - startedAgo
    } else {
      limit = testTimeLimit - totalTimeTaken
    }
    if(oneSecRound) {
      limit = max(limit, 1)
    }
  }
  return(limit)
}

getTemplateTimeLimit = function(testTimeLimit) {
  itemTimeLimit = as.numeric(settings$itemTimeLimit)
  limit = testTimeLimit
  if(itemTimeLimit > 0) {
    if(limit > 0) {
      limit = min(limit, itemTimeLimit)
    } else {
      limit = itemTimeLimit
    }
  }
  return(limit)
}

params = getTemplateParams()
if(!is.na(settings$itemTemplateParamsModule) && settings$itemTemplateParamsModule != "") {
  params = concerto.test.run(settings$itemTemplateParamsModule, params=list(
    params = params,
    items = items[itemsIndices,],
    itemsAdministered = itemsAdministered,
    settings=settings,
    theta=theta,
    sem=sem,
    session=session,
    scores=scores,
    traitScores=traitScores
  ))$params
}

testTimeLeft = getTestTimeLeft(T)
templateTimeLimit = getTemplateTimeLimit(testTimeLeft)

#save state
if(settings$sessionResuming == 1) {
  state$nextItemsIds = items[itemsIndices, "id"]
  state$itemsIds = items[,"id"]
  state$page = page
  state$testTimeStarted = testTimeStarted

  sessionTable = fromJSON(settings$sessionTable)
  concerto.table.query("
UPDATE {{table}} 
SET {{stateCol}}='{{resumeState}}'
WHERE id={{id}}", params=list(
  table = sessionTable$table,
  stateCol = sessionTable$columns$state,
  resumeState = toJSON(state),
  id = session$id
))
}

if(!is.list(bgWorkers)) {
  bgWorkers = list()
}
templateResponse = concerto.template.show(
  templateId=settings$itemTemplate, 
  html=settings$itemTemplateHtml,
  params=params, 
  timeLimit=templateTimeLimit,
  bgWorkers=bgWorkers
)

if(!is.na(settings$templateResponseModule) && settings$templateResponseModule != "") {
  templateResponse = concerto.test.run(settings$templateResponseModule, params=list(
    settings = settings,
    params = params,
    templateResponse = templateResponse
  ))$templateResponse
}

timeTaken = as.numeric(templateResponse$timeTaken)
totalTimeTaken = totalTimeTaken + timeTaken

testTimeLeft = getTestTimeLeft(F)

.branch = "submitted"
if(templateResponse$isTimeout == 1) { .branch = "outOfTime" }
