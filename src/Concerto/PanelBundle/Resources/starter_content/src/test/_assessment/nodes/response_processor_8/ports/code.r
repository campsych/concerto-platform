getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

saveResponse = function(score, trait, item) {
  responseBank = fromJSON(settings$responseBank)
  if(!is.character(responseBank$table) || nchar(responseBank$table) == 0) {
    concerto.log("no response bank defined, skipping response saving")
    return(NULL)
  }

  params = list(
    table=responseBank$table,
    sessionIdColumn=responseBank$columns$session_id,
    sessionId=getSessionId(session),
    itemIdColumn=responseBank$columns$item_id,
    itemId=item$id,
    responseColumn=responseBank$columns$response,
    responseValue=templateResponse[[paste0("r",item$id)]],
    scoreColumn=responseBank$columns$score,
    score=score,
    timeTakenColumn=responseBank$columns$timeTaken,
    timeTaken=templateResponse$timeTaken, #could use time difference
    thetaColumn=responseBank$columns$theta,
    theta=theta,
    semColumn=responseBank$columns$sem,
    sem=sem,
    traitColumn=responseBank$columns$trait,
    trait=trait
  )

  sql = NULL
  response = concerto.table.query("SELECT id FROM {{table}} WHERE {{itemIdColumn}}={{itemId}} AND {{sessionIdColumn}}='{{sessionId}}' LIMIT 1", params=params)
  if(dim(response)[1] > 0) {
    params$id = response[1,"id"]
    sql = "
UPDATE {{table}} SET
{{responseColumn}}='{{responseValue}}',
{{scoreColumn}}={{score}},
{{timeTakenColumn}}={{timeTaken}},
{{thetaColumn}}={{theta}},
{{semColumn}}={{sem}},
{{traitColumn}}='{{trait}}'
WHERE id={{id}}
"
  } else {
    sql = "
INSERT INTO {{table}} 
({{itemIdColumn}}, {{responseColumn}}, {{scoreColumn}}, {{timeTakenColumn}}, {{sessionIdColumn}}, {{thetaColumn}}, {{semColumn}}, {{traitColumn}}) 
VALUES ({{itemId}}, '{{responseValue}}', {{score}}, {{timeTaken}}, '{{sessionId}}', {{theta}}, {{sem}}, IF('{{trait}}' = '', NULL, '{{trait}}'))
"
  }

  concerto.table.query(sql, params)
}

for(i in 1:length(itemsIndices)) {
  itemIndex = itemsIndices[i]
  item = items[itemIndex,]
  saveResponse(currentScores[i], currentTraits[i], item)
}

direction = 1
if(settings$order != "cat" && settings$canGoBack == "1" && page > 1 && templateResponse$buttonPressed == "back") {
  direction = -1
}