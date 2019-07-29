getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

saveResponse = function(responseBank, session, response, score, item, theta, sem) {
  responseBank = fromJSON(responseBank)
  if(is.null(responseBank$table)) {
    concerto.log("no response bank defined, skipping response saving")
    return(NULL)
  }

  sql = "
INSERT INTO {{table}} 
({{itemIdColumn}}, {{responseColumn}}, {{scoreColumn}}, {{timeTakenColumn}}, {{sessionIdColumn}}, {{thetaColumn}}, {{semColumn}}) 
VALUES ({{itemId}}, '{{responseValue}}', {{score}}, {{timeTaken}}, '{{sessionId}}', {{theta}}, {{sem}})
"
  concerto.table.query(sql, list(
    table=responseBank$table,
    sessionIdColumn=responseBank$columns$session_id,
    sessionId=getSessionId(session),
    itemIdColumn=responseBank$columns$item_id,
    itemId=item$id,
    responseColumn=responseBank$columns$response,
    responseValue=response[[paste0("r",item$id)]],
    scoreColumn=responseBank$columns$score,
    score=score,
    timeTakenColumn=responseBank$columns$timeTaken,
    timeTaken=response$timeTaken, #could use time difference
    thetaColumn=responseBank$columns$theta,
    theta=theta,
    semColumn=responseBank$columns$sem,
    sem=sem
  ))
  return(concerto.table.lastInsertId())
}

itemsAdministered = unique(c(itemsAdministered, itemIndex))
responseId = saveResponse(settings$responseBank, session, response, score, item, theta, sem)
