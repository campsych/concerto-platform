getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

saveResponses = function(responseBank, sessionId, templateResponse, currentItems, currentItemsIndices, scores) {
  responseBank = fromJSON(responseBank)
  if(!is.character(responseBank$table) || nchar(responseBank$table) == 0) {
    concerto.log("no response bank defined, skipping response saving")
    return(NULL)
  }
  
  sql = "SELECT * FROM {{table}} WHERE {{itemIdColumn}} IN ({{itemsIds}}) AND {{sessionIdColumn}}='{{sessionId}}'"
  responses = concerto.table.query(sql, params=list(
    table=responseBank$table,
    itemIdColumn=responseBank$columns$item_id,
    itemsIds=paste(currentItems[,"id"], collapse=","),
    sessionIdColumn=responseBank$columns$session_id,
    sessionId=sessionId
  ))

  queries = NULL
  for(i in 1:dim(currentItems)[1]) {
    found = F
    item = as.list(currentItems[i,])
    sql = ""
    if(dim(responses)[1] > 0) {
      for(j in 1:dim(responses)[1]) {
        response = as.list(responses[j,])
        if(response$item_id == item$id) {
          found = T
          sql = "
  UPDATE {{table}} 
  SET 
  {{itemIdColumn}}={{itemId}}, 
  {{responseColumn}}='{{response}}', 
  {{timeTakenColumn}}={{timeTaken}}, 
  {{sessionIdColumn}}='{{sessionId}}', 
  {{scoreColumn}}={{score}}, 
  {{traitColumn}}='{{trait}}'
  WHERE {{itemIdColumn}}={{itemId}} AND {{sessionIdColumn}}='{{sessionId}}'
  "
          break
        }
      }
    }
    if(!found) {
      sql = "
INSERT INTO {{table}} ({{itemIdColumn}}, {{responseColumn}}, {{timeTakenColumn}}, {{sessionIdColumn}}, {{scoreColumn}}, {{traitColumn}})
VALUES ({{itemId}}, '{{responseValue}}', {{timeTaken}}, '{{sessionId}}', {{score}}, '{{trait}}')
"
    }
    sql = concerto.table.insertParams(sql, list(
      table=responseBank$table,
      sessionIdColumn=responseBank$columns$session_id,
      sessionId=sessionId,
      itemIdColumn=responseBank$columns$item_id,
      itemId=item$id,
      responseColumn=responseBank$columns$response,
      responseValue=templateResponse[[paste0("r",item$id)]],
      timeTakenColumn=responseBank$columns$timeTaken,
      timeTaken=response$timeTaken, #could use time difference
      scoreColumn=responseBank$columns$score,
      score=scores[currentItemsIndices[i]],
      traitColumn=responseBank$columns$trait,
      trait=item$trait
    ))
    queries = c(queries, sql)
  }
  for(i in 1:length(queries)) {
  	concerto.table.query(queries[i])  
  }
}

sessionId = getSessionId(session)
saveResponses(settings$responseBank, sessionId, response, currentItems, currentItemsIndices, scores)

direction = 1
itemsAdministered = unique(c(itemsAdministered, currentItemsIndices)) 
if(settings$canGoBack == "1" && page > 1 && response$buttonPressed == "back") {
  direction = -1
}
concerto.log(itemsAdministered, "itemsAdministered")
concerto.log(direction, "direction")
