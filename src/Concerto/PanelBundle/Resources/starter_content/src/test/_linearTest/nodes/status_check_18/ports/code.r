isOutOfTime = function(testTimeLimit, testTimeStarted, itemTimeLimit, itemTimeFullRequired) {
  startedAgo = as.numeric(Sys.time()) - testTimeStarted
  testTimeLeft = testTimeLimit - startedAgo
  if(testTimeLimit > 0) {
    if(testTimeLeft <= 0) { return(T) }
    if(testTimeLeft < itemTimeLimit && itemTimeFullRequired) { return(T) }
  }
  return(F)
}

getBranch = function(testTimeLimit, testTimeStarted, itemTimeLimit, itemTimeFullRequired, itemNumLimit, itemsNum, direction, itemsAdministered) {
  if(isOutOfTime(testTimeLimit, testTimeStarted, itemTimeLimit, itemTimeFullRequired)) {
    concerto.log("time out", "test status")
    return("stop")
  }
  
  maxItems = dim(items)[1]
  itemNumLimit = as.numeric(settings$itemNumLimit)
  if(itemNumLimit > 0) { maxItems = min(maxItems, itemNumLimit) }
  totalPages = ceiling(maxItems / as.numeric(settings$itemsPerPage))
  
  if(length(itemsAdministered) >= maxItems) {
    if(direction > 0 && totalPages == page) {
      concerto.log("maximum items reached", "test status")
      return("stop")
    }
  }
  
  concerto.log("continue", "test status")
  return("continue")
}

.branch = getBranch(
  as.numeric(settings$testTimeLimit), 
  testTimeStarted, 
  as.numeric(settings$itemTimeLimit), 
  settings$itemTimeFullRequired == "1", 
  as.numeric(settings$itemNumLimit), 
  dim(items)[1],
  direction,
  itemsAdministered
)
