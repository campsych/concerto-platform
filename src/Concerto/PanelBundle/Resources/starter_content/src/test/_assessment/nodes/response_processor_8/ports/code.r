getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

isSkipped = function(item) {
  skippedTemplateResponse = templateResponse[[paste0("skip",item$id)]]
  if(settings$canSkipItems == 1 && !is.null(skippedTemplateResponse) && skippedTemplateResponse == 1) {
    return(T)
  }
  return(F)
}

saveResponse = function(score, trait, item, skipped) {
  responseBank = fromJSON(settings$responseBank)
  if(!is.character(responseBank$table) || nchar(responseBank$table) == 0) {
    return(NULL)
  }

  hasSkippedColumn = !is.null(responseBank$columns$skipped) && !is.na(responseBank$columns$skipped) && responseBank$columns$skipped != ""
  hasCreatedTimeColumn = !is.null(responseBank$columns$createdTime) && !is.na(responseBank$columns$createdTime) && responseBank$columns$createdTime != ""
  hasUpdateTimeColumn = !is.null(responseBank$columns$updateTime) && !is.na(responseBank$columns$updateTime) && responseBank$columns$updateTime != ""

  params = list(
    table = responseBank$table,
    sessionIdColumn = responseBank$columns$session_id,
    sessionId = getSessionId(session),
    itemIdColumn = responseBank$columns$item_id,
    itemId = item$id,
    responseColumn = responseBank$columns$response,
    responseValue = templateResponse[[paste0("r",item$id)]],
    skippedColumn = responseBank$columns$skipped,
    skipped = if(skipped) { 1 } else { 0 },
    scoreColumn = responseBank$columns$score,
    score = score,
    timeTakenColumn = responseBank$columns$timeTaken,
    timeTaken = templateResponse$timeTaken, #could use time difference
    thetaColumn = responseBank$columns$theta,
    theta = theta,
    semColumn = responseBank$columns$sem,
    sem = sem,
    traitColumn = responseBank$columns$trait,
    trait = trait,
    createdTimeColumn = responseBank$columns$createdTime,
    updateTimeColumn = responseBank$columns$updateTime
  )

  sql = NULL
  response = concerto.table.query("
SELECT id 
FROM {{table}} 
WHERE {{itemIdColumn}}={{itemId}} AND {{sessionIdColumn}}='{{sessionId}}' 
LIMIT 1", params=params)
  responseExist = dim(response)[1] > 0
  responseId = NULL

  if(responseExist) {
    responseId = response[1,"id"]
    params$id = responseId
    sql = "
UPDATE {{table}} SET
{{responseColumn}} = '{{responseValue}}',
{{scoreColumn}} = IF('{{score}}' = '', NULL, '{{score}}'),
{{timeTakenColumn}} = {{timeTaken}},
{{thetaColumn}} = {{theta}},
{{semColumn}} = {{sem}},
{{traitColumn}} = IF('{{trait}}' = '', NULL, '{{trait}}')"

    if(hasSkippedColumn) {
      sql = paste0(sql, ",{{skippedColumn}} = {{skipped}} ")
    }
    if(hasUpdateTimeColumn) {
      sql = paste0(sql, ",{{updateTimeColumn}} = CURRENT_TIMESTAMP ")
    }

    sql = paste0(sql, "
WHERE id={{id}}")
  } else {
    sql = "
INSERT INTO {{table}} 
(
{{itemIdColumn}}
,{{responseColumn}}
,{{scoreColumn}}
,{{timeTakenColumn}}
,{{sessionIdColumn}}
,{{thetaColumn}}
,{{semColumn}}
,{{traitColumn}}"

    if(hasSkippedColumn) {
      sql = paste0(sql, ",{{skippedColumn}}")
    }
    if(hasCreatedTimeColumn) {
      sql = paste0(sql, ",{{createdTimeColumn}}")
    }
    if(hasUpdateTimeColumn) {
      sql = paste0(sql, ",{{updateTimeColumn}}")
    }

    sql = paste0(sql, "
) 
VALUES (
{{itemId}}
,'{{responseValue}}'
,IF('{{score}}' = '', NULL, '{{score}}')
,{{timeTaken}}
,'{{sessionId}}'
,{{theta}}
,{{sem}}
,IF('{{trait}}' = '', NULL, '{{trait}}')")

    if(hasSkippedColumn) {
      sql = paste0(sql, ",{{skipped}}")
    }
    if(hasCreatedTimeColumn) {
      sql = paste0(sql, ",CURRENT_TIMESTAMP")
    }
    if(hasUpdateTimeColumn) {
      sql = paste0(sql, ",CURRENT_TIMESTAMP")
    }

    sql = paste0(sql, "
)")
  }

  concerto.table.query(sql, params)
  if(!responseExist) {
    responseId = concerto.table.lastInsertId()
  }

  if(!is.na(settings$responseSavedModule) && settings$responseSavedModule != "") {
    concerto.test.run(settings$responseSavedModule, params=list(
      settings = settings,
      id = responseId,
      response = params,
      theta = theta,
      sem = sem,
      prevTheta = prevTheta,
      prevSem = prevSem,
      score = score,
      value = params$responseValue,
      session = session,
      item = item,
      skipped = skipped,
      timeTaken = params$timeTaken,
      templateResponse = templateResponse
    ))
  }
}

for(i in 1:length(itemsIndices)) {
  itemIndex = itemsIndices[i]
  item = items[itemIndex,]
  skipped = isSkipped(item)
  saveResponse(currentScores[i], currentTraits[i], item, skipped)
}

direction = 1
if(settings$order != "cat" && settings$canGoBack == "1" && page > 1 && templateResponse$buttonPressed == "back") {
  direction = -1
}
