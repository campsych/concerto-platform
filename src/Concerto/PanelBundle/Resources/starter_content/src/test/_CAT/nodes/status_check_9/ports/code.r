isOutOfTime = function(testTimeLimit, testTimeLeft, itemTimeLimit, itemTimeFullRequired) {
  if(testTimeLimit > 0) {
    if(testTimeLeft <= 1) { return(T) }
    if(testTimeLeft < itemTimeLimit && itemTimeFullRequired) { return(T) }
  }
  return(F)
}

getBranch = function(testTimeLimit, testTimeLeft, itemTimeLimit, itemTimeFullRequired, itemNumLimit, minAccuracy, itemsAdministered, itemsNum, sem) {
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

  if(itemNumLimit > 0 && length(itemsAdministered) >= itemNumLimit || length(itemsAdministered) >= itemsNum) {
    concerto.log("maximum items reached", "test status")
    return("stop")
  }

  if(minAccuracy != 0 && minAccuracy >= sem) {
    concerto.log("minimum accuracy", "test status")
    return("stop")
  }
  concerto.log("continue", "test status")
  return("continue")
}

.branch = getBranch(
  as.numeric(settings$testTimeLimit),
  testTimeLeft,
  as.numeric(settings$itemTimeLimit), 
  settings$itemTimeFullRequired == "1", 
  as.numeric(settings$itemNumLimit), 
  as.numeric(settings$minAccuracy), 
  itemsAdministered, 
  dim(items)[1], 
  sem
)
