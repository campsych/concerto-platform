getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

getSafeItems = function(nextItems) {
  itemsNum = dim(nextItems)[1]
  if(itemsNum > 0) {
    for(i in 1:itemsNum) {
      item = as.list(nextItems[i,])

      if(is.character(item$responseOptions)) { item$responseOptions = fromJSON(item$responseOptions) }
      responseOptionsRandomOrder = item$responseOptions$optionsRandomOrder == "1"
      orderedOptions = c()

      if(length(item$responseOptions$options) > 0) {
        if(responseOptionsRandomOrder) {
          orderedOptions = item$responseOptions$options[sample(1:length(item$responseOptions$options))]
        } else {
          orderedOptions = item$responseOptions$options
        }
      }
      orderedOptions = lapply(orderedOptions, function(elem) {
        elem$score = NULL
        return(elem)
      })

      item$responseOptions$options = orderedOptions
      item$responseOptions$openScore = NULL
      item$responseOptions$openCorrect = NULL
      item$responseOptions = toJSON(item$responseOptions)
      
      nextItems[i,] = item
    }
  }
  return(nextItems)
}

getSafePastResponses = function(nextItems, session, responseBank) {
  responseBank = fromJSON(responseBank)
  if(is.null(responseBank$table)) {
    concerto.log("no response bank defined")
    return(NULL)
  }

  sql = "
SELECT * FROM {{table}}
WHERE 
{{sessionIdColumn}}='{{sessionId}}' AND
{{itemIdColumn}} IN ({{itemIds}})
"
  responses = concerto.table.query(sql, list(
    table=responseBank$table,
    sessionIdColumn=responseBank$columns$session_id,
    sessionId=getSessionId(session),
    itemIdColumn=responseBank$columns$item_id,
    itemIds=paste(nextItems[,"id"], collapse=",")
  ))
  if(dim(responses)[1] > 0) {
    responses[,"score"] = NULL
    return(responses)
  }
  return(NULL)
}

concerto.log(prevPage, "previous page")
concerto.log(direction, "direction")
nextPage = as.numeric(prevPage) + as.numeric(direction)

pageFirstItemIndex = (nextPage - 1) * as.numeric(settings$itemsPerPage) + 1
pageLastItemIndex = min(nextPage * as.numeric(settings$itemsPerPage), dim(items)[1])

nextItemsIndices = pageFirstItemIndex:pageLastItemIndex
concerto.log(nextItemsIndices, "next item indices")
nextItems = items[nextItemsIndices,]
nextItemsSafe = getSafeItems(nextItems)
pastResponsesSafe = getSafePastResponses(nextItems, session, settings$responseBank)
