isOutOfTime = function(testTimeLimit, testTimeLeft, itemTimeLimit, itemTimeFullRequired) {
  if(testTimeLimit > 0) {
    if(testTimeLeft <= 1) { return(T) }
    if(testTimeLeft < itemTimeLimit && itemTimeFullRequired) { return(T) }
  }
  return(F)
}

getBranch = function(testTimeLimit, itemTimeLimit, itemTimeFullRequired, itemNumLimit, minAccuracy, minAccuracyMinItems, itemsNum) {
  outOfTime = isOutOfTime(
    testTimeLimit, 
    testTimeLeft,
    itemTimeLimit, 
    itemTimeFullRequired
  )

  if(outOfTime) {
    concerto.log("time out", "test status")
    return("stop")
  }

  maxItems = dim(items)[1]
  itemNumLimit = as.numeric(settings$itemNumLimit)
  if(itemNumLimit > 0) { maxItems = min(maxItems, itemNumLimit) }
  totalPages = ceiling(maxItems / as.numeric(settings$itemsPerPage))

  if(itemNumLimit > 0 && length(itemsAdministered) >= itemNumLimit || length(itemsAdministered) >= itemsNum) {
    if(direction > 0 && totalPages == page) {
      concerto.log("maximum items reached", "test status")
      return("stop")
    }
  }

  if(minAccuracy != 0 && minAccuracy >= sem && minAccuracyMinItems <= length(itemsAdministered)) {
    concerto.log("minimum accuracy", "test status")
    return("stop")
  }
  concerto.log("continue", "test status")
  return("continue")
}

.branch = getBranch(
  as.numeric(settings$testTimeLimit),
  as.numeric(settings$itemTimeLimit), 
  settings$itemTimeFullRequired == "1", 
  as.numeric(settings$itemNumLimit), 
  as.numeric(settings$minAccuracy), 
  as.numeric(settings$minAccuracyMinItems),
  dim(items)[1]
)
