getData = function(itemsSafe, responsesSafe, canGoBack, templateParams, responseRequired, responseRequiredAlert, page, totalPages) {
  templateParams$items = itemsSafe
  templateParams$canGoBack = canGoBack
  templateParams$responses = responsesSafe
  templateParams$responseRequired = responseRequired
  templateParams$responseRequiredAlert = responseRequiredAlert
  templateParams$page = page
  templateParams$totalPages = totalPages

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

canGoBack = if(settings$canGoBack == "1" && page > 1) { 1 } else { 0 }
maxItems = dim(items)[1]
itemNumLimit = as.numeric(settings$itemNumLimit)
if(itemNumLimit > 0) { maxItems = min(maxItems, itemNumLimit) }
totalPages = ceiling(maxItems / as.numeric(settings$itemsPerPage))
response = concerto.template.show(
  settings$itemTemplate, 
  params=getData(
    itemsSafe, 
    responsesSafe, 
    canGoBack, 
    templateParams, 
    settings$responseRequired, 
    settings$responseRequiredAlert, 
    page, 
    totalPages
  ), 
  timeLimit=getTimeLimit(
    testTimeStarted, 
    as.numeric(settings$testTimeLimit), 
    as.numeric(settings$itemTimeLimit)
  )
)

.branch = "submitted"
if(response$isTimeout == 1) { .branch = "outOfTime" }
