getTemplateParams = function() {
  templateParams$items = data.frame(itemSafe)
  templateParams$responseRequired = settings$responseRequired
  templateParams$responseRequiredAlert = settings$responseRequiredAlert
  templateParams$footer = settings$footer
  templateParams$instructions = settings$instructions
  templateParams$logo = settings$logo
  templateParams$nextButtonLabel = settings$nextButtonLabel
  templateParams$title = settings$title

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

if(!concerto.template.isResponseQueued()) {
  params = getTemplateParams()
  if(!is.na(settings$itemTemplateParamsModule) && settings$itemTemplateParamsModule != "") {
    params = concerto.test.run(settings$itemTemplateParamsModule, params=list(
      params = params,
      item = item,
      itemsAdministered = itemsAdministered,
      settings=settings
    ))$params
  }
}

testTimeLeft = getTestTimeLeft(T)

templateTimeLimit = getTemplateTimeLimit(testTimeLeft)

templateResponse = concerto.template.show(
  templateId=settings$itemTemplate, 
  html=settings$itemTemplateHtml,
  params=params, 
  timeLimit=templateTimeLimit
)

timeTaken = as.numeric(templateResponse$timeTaken)
totalTimeTaken = totalTimeTaken + timeTaken

testTimeLeft = getTestTimeLeft(F)

.branch = "submitted"
if(templateResponse$isTimeout == 1) { .branch = "outOfTime" }
