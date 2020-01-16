exposureMaxItems = as.numeric(settings$exposureMaxItems)
exposureLimit = as.numeric(settings$exposureLimit)
exposureMinSessions = as.numeric(settings$exposureMinSessions)

concerto.log(exposureMaxItems, "exposure max items")

if(is.na(exposureMaxItems) || exposureMaxItems == 0) { 
  return()
}

if(!is.list(session) || is.null(session$id)) {
  concerto.log("session is required for item exposure")
  return()
}

responseTable = fromJSON(settings$responseBank)
if(!is.character(responseTable$table) || nchar(responseTable$table) == 0) {
  concerto.log("no response bank defined, skipping exposure")
  return()
}

responsesRecords = concerto.table.query("
SELECT id, 
{{itemIdCol}} AS item_id
FROM {{table}} 
WHERE {{sessionIdCol}}={{sessionId}}", params=list(
  itemIdCol = responseTable$columns$item_id,
  table = responseTable$table,
  sessionIdCol = responseTable$columns$session_id,
  sessionId = session$id
))

itemsAnswered = items[items[,"id"] %in% responsesRecords[,"item_id"],]
itemsAnsweredNum = dim(itemsAnswered)[1]
itemsLeft = items
if(itemsAnsweredNum > 0) {
  itemsLeft = items[-as.numeric(rownames(itemsAnswered)),]
}
itemsLeftNum = dim(itemsLeft)[1]

if(itemsLeftNum == 0) {
  return()
}

sessionNum = concerto.table.query("
SELECT COUNT(DISTINCT session_id) AS sessionNum
FROM {{table}}", params=list(
  table = responseTable$table
))[1,1]

if(sessionNum == 0 || exposureMinSessions > sessionNum) {
  concerto.log(sessionNum, "session number to low for item exposure")
  return()
}

exposureCount = concerto.table.query("
SELECT COUNT(*) AS sessionNum,
{{itemIdCol}} AS item_id
FROM {{table}}
WHERE {{itemIdCol}} IN ({{itemsLeftIds}})
GROUP BY {{itemIdCol}}", params=list(
  itemIdCol = responseTable$columns$item_id,
  table = responseTable$table,
  itemsLeftIds=paste(itemsLeft[,"id"], collapse=",")
))

excludedIndices = c()
for(i in 1:itemsLeftNum) {
  itemIndex = itemsAnsweredNum + i
  exposure = exposureCount[i, "sessionNum"] / sessionNum
  
  if(exposure >= exposureLimit) {
    excludedIndices = c(excludedIndices, itemIndex)
  }
  
  if(length(excludedIndices) >= exposureMaxItems) {
    break
  }
}

concerto.log(excludedIndices, "excluded item indices by item exposure")
if(length(excludedIndices) > 0) {
  items = items[-excludedIndices,]
  paramBank = paramBank[-excludedIndices,]
}