getTemplateParams = function(itemSafe, templateParams, responseRequired, responseRequiredAlert) {
  templateParams$items = data.frame(itemSafe)
  templateParams$responseRequired = responseRequired
  templateParams$responseRequiredAlert = responseRequiredAlert

  return(templateParams)
}

getTimeLimit = function(testTimeStarted, testTimeLimit, itemTimeLimit) {
  limit = 0
  startedAgo = as.numeric(Sys.time()) - testTimeStarted
  testTimeLeft = 0
  if(testTimeLimit > 0) {
    testTimeLeft = max(testTimeLimit - startedAgo, 1)
    limit = testTimeLeft
  }
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

testTimeLeft = getTimeLimit(
  testTimeStarted, 
  as.numeric(settings$testTimeLimit) + as.numeric(settings$testTimeLimitOffset), 
  as.numeric(settings$itemTimeLimit)
)

response = concerto.template.show(
  settings$itemTemplate, 
  params=params, 
  timeLimit=testTimeLeft
)

.branch = "submitted"
if(response$isTimeout == 1) { .branch = "outOfTime" }
