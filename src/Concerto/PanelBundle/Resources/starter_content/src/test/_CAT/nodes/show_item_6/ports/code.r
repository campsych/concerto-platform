getTemplateParams = function(itemSafe, templateParams, responseRequired, responseRequiredAlert) {
  templateParams$items = data.frame(itemSafe)
  templateParams$responseRequired = responseRequired
  templateParams$responseRequiredAlert = responseRequiredAlert

  return(templateParams)
}

getTestTimeLeft = function(testTimeLimitType, testTimeStarted, testTimeLimit, totalTimeTaken, oneSecRound=T) {
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

getTemplateTimeLimit = function(testTimeLimit, itemTimeLimit) {
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
  params = getTemplateParams(itemSafe, templateParams, settings$responseRequired, settings$responseRequiredAlert)
  if(!is.na(settings$itemTemplateParamsModule) && settings$itemTemplateParamsModule != "") {
    params = concerto.test.run(settings$itemTemplateParamsModule, params=list(
      params = params,
      item = item,
      itemsAdministered = itemsAdministered,
      settings=settings
    ))$params
  }
}

testTimeLeft = getTestTimeLeft(
  settings$testTimeLimitType,
  testTimeStarted, 
  as.numeric(settings$testTimeLimit) + as.numeric(settings$testTimeLimitOffset), 
  totalTimeTaken,
  T
)

templateTimeLimit = getTemplateTimeLimit(
  testTimeLeft,
  as.numeric(settings$itemTimeLimit)
)

templateResponse = concerto.template.show(
  settings$itemTemplate, 
  params=params, 
  timeLimit=templateTimeLimit
)

timeTaken = as.numeric(templateResponse$timeTaken)
totalTimeTaken = totalTimeTaken + timeTaken

testTimeLeft = getTestTimeLeft(
  settings$testTimeLimitType,
  testTimeStarted, 
  as.numeric(settings$testTimeLimit) + as.numeric(settings$testTimeLimitOffset), 
  totalTimeTaken,
  F
)

.branch = "submitted"
if(templateResponse$isTimeout == 1) { .branch = "outOfTime" }
