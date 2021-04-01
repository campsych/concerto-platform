isOutOfTime = function(testTimeLimit, testTimeLeft, itemTimeLimit, itemTimeFullRequired) {
  if(testTimeLimit > 0) {
    if(testTimeLeft <= 1) { return(T) }
    if(testTimeLeft < itemTimeLimit && itemTimeFullRequired) { return(T) }
  }
  return(F)
}

getStopReason = function(testTimeLimit, itemTimeLimit, itemTimeFullRequired, itemNumLimit, minAccuracy, minAccuracyMinItems, itemsNum, excludedItems) {
  outOfTime = isOutOfTime(
    testTimeLimit, 
    testTimeLeft,
    itemTimeLimit, 
    itemTimeFullRequired
  )

  if(outOfTime) {
    return("timeOut")
  }

  maxItems = dim(items)[1]
  itemNumLimit = as.numeric(settings$itemNumLimit)
  if(itemNumLimit > 0) { maxItems = min(maxItems, itemNumLimit) }
  totalPages = ceiling(maxItems / as.numeric(settings$itemsPerPage))

  if(itemNumLimit > 0 && length(itemsAdministered) >= itemNumLimit || length(itemsAdministered) - length(excludedItems) >= itemsNum) {
    if(direction > 0 && totalPages == page) {
      return("maxItems")
    }
  }

  if(minAccuracy != 0 && minAccuracy >= sem && minAccuracyMinItems <= length(itemsAdministered)) {
    return("minAccuracy")
  }

  return(NULL)
}

stopReason = getStopReason(
  as.numeric(settings$testTimeLimit),
  as.numeric(settings$itemTimeLimit), 
  settings$itemTimeFullRequired == "1", 
  as.numeric(settings$itemNumLimit), 
  as.numeric(settings$minAccuracy), 
  as.numeric(settings$minAccuracyMinItems),
  dim(items)[1],
  excludedItems
)
if(!is.na(settings$stopCheckModule) && settings$stopCheckModule != "") {
  stopReason = concerto.test.run(settings$stopCheckModule, params=list(
    stopReason = stopReason,
    settings = settings,
    theta = theta,
    sem = sem,
    itemsAdministered = itemsAdministered,
    session = session,
    responses = responses,
    scores = scores,
    items = items,
    templateResponse = templateResponse,
    excludedItems = excludedItems
  ))$stopReason
}

concerto.log(stopReason, "stopReason")

if(is.null(stopReason)) {
  .branch = "continue"
} else {
  .branch = "stop"
}