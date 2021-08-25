library(catR)

getSessionId = function(session) {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

getSafeItems = function(items, extraFields) {
  safeItems = NULL
  extraFields = fromJSON(extraFields)

  for(i in 1:dim(items)[1]) {
    item = as.list(items[i,])

    concerto.log(item, "ITEM")

    scoreColName = "responseScore1"
    scoreColIndex = 1
    while(scoreColName %in% ls(item)) {
      item[[scoreColName]] = NULL
      scoreColIndex = scoreColIndex + 1
      scoreColName = paste0("responseScore", scoreColIndex)
    }

    if(!is.null(item$responseOptions) && item$responseOptions != "") {
      if(is.character(item$responseOptions)) {
        item$responseOptions = tryCatch({
          fromJSON(item$responseOptions)
        }, error = function(message) {
          concerto.log(message)
          stop(paste0("item #", item$id, " contains invalid JSON in responseOptions field"))
        })
      }
      responseOptionsRandomOrder = 0
      if(!is.null(item$responseOptions$optionsRandomOrder)) {
        responseOptionsRandomOrder = item$responseOptions$optionsRandomOrder == 1
      }
      orderedOptions = c()

      if(length(item$responseOptions$options) > 0) {
        if(responseOptionsRandomOrder) {
          orderedOptions = item$responseOptions$options[sample(1:length(item$responseOptions$options))]
        } else {
          orderedOptions = item$responseOptions$options
        }
        #fixed indices
        for(i in 1:length(orderedOptions)) {
          option = orderedOptions[[i]]
          fixedIndex = as.numeric(option$fixedIndex)

          loopNum = 1
          while(length(fixedIndex) > 0 && !is.na(fixedIndex) && fixedIndex != i && loopNum <= length(orderedOptions)) {
            replacedOption = orderedOptions[[fixedIndex]]
            orderedOptions[[i]] = replacedOption
            orderedOptions[[fixedIndex]] = option

            option = orderedOptions[[i]]
            fixedIndex = as.numeric(option$fixedIndex)
            loopNum = loopNum + 1
          }
        }
      }
      item$responseOptions$options = orderedOptions
      item$responseOptions$defaultScore = NULL
      item$responseOptions$scoreMap = NULL
      item$responseOptions = toJSON(item$responseOptions)
    }

    for(extraField in extraFields) {
      if(extraField$sensitive == 1) {
        item[[extraField$name]] = NULL
      }
    }

    safeItems = rbind(safeItems, item)
  }

  return(safeItems)
}

getSafePastResponses = function(nextItems, nextItemsIndices) {
  responseBank = fromJSON(settings$responseBank)
  if(is.null(responseBank$table) || responseBank$table == "") {
    concerto.log("no response bank defined")

    pastResponses = NULL
    for(nextItemIndex in nextItemsIndices) {
      responseIndex = which(nextItemIndex == itemsAdministered)
      if(length(responseIndex) > 0) {
        pastResponses = rbind(pastResponses, data.frame(
          item_id = items[nextItemIndex, "id"],
          response = responses[responseIndex],
          skipped = 0
        ))
      }
    }
    return(pastResponses)
  }

  sql = "
SELECT 
{{itemIdColumn}} AS item_id,
{{responseColumn}} AS response,
{{skippedColumn}} AS skipped
FROM {{table}}
WHERE 
{{sessionIdColumn}}='{{sessionId}}' AND
{{itemIdColumn}} IN ({{itemIds}})
"
  pastResponses = concerto.table.query(sql, list(
    table=responseBank$table,
    responseColumn=responseBank$columns$response,
    skippedColumn=responseBank$columns$skipped,
    sessionIdColumn=responseBank$columns$session_id,
    sessionId=getSessionId(session),
    itemIdColumn=responseBank$columns$item_id,
    itemIds=paste(nextItems[,"id"], collapse=",")
  ))
  if(dim(pastResponses)[1] > 0) {
    return(pastResponses)
  }
  return(NULL)
}

itemsNum = dim(items)[1]
nextItemsIndices = which(items$id %in% resumedItemsIds)
itemsPerPage = as.numeric(settings$itemsPerPage)
nextPage = as.numeric(prevPage) + as.numeric(direction)

if(length(nextItemsIndices) == 0) {

  cbGroup = NULL
  cbControl = NULL

  if(settings$order == "cat") {

    #content balancing
    cbProps = fromJSON(settings$contentBalancing)
    concerto.log(cbProps, "cbProps")
    if(length(cbProps) > 0) {
      cbGroup = as.character(items[,"trait"])
      cbControl = list(
        names=NULL,
        props=NULL
      )
      for(i in 1:length(cbProps)) {
        cbControl$names = c(cbControl$names, cbProps[[i]]$name)
        cbControl$props = c(cbControl$props, as.numeric(cbProps[[i]]$proportion))
      }
      concerto.log(cbControl, "cbControl")
    }

    for(onPageIndex in 1:itemsPerPage) {
      #fixed indices
      inTestIndex = length(itemsAdministered) + onPageIndex
      fixedItemIndex = which(items$fixedIndex == inTestIndex)[1]
      if(!is.na(fixedItemIndex) && !(fixedItemIndex %in% excludedItems)) {
        nextItemsIndices = c(nextItemsIndices, fixedItemIndex)
        next
      }

      isAnyItemLeft = itemsNum - length(excludedItems) > length(itemsAdministered)
      if(isAnyItemLeft) {
        nAvailable = NULL
        for(i in 1:dim(items)[1]) {
          item = items[i,]
          available = if((!is.null(item$fixedIndex) && !is.na(item$fixedIndex) && item$fixedIndex > 0) || i %in% excludedItems) { 
            0 
          } else { 
            1 
          }
          nAvailable = c(nAvailable, available)
        }
        randomesque = as.numeric(settings$nextItemRandomesque)
        d = as.numeric(settings$d)

        result = tryCatch({
          nextItem(paramBank, model=settings$model, theta=theta, out=itemsAdministered, x=scores, nAvailable=nAvailable, criterion=settings$nextItemCriterion, method=settings$scoringMethod, randomesque=randomesque, D=d, cbGroup=cbGroup, cbControl=cbControl)
        }, error=function(ex) {
          concerto.log(ex, "potentialy not possible to satisfy CB rule")
          if(!is.null(cbGroup) && !is.null(cbControl)) {
            return(nextItem(paramBank, model=settings$model, theta=theta, out=itemsAdministered, x=scores, nAvailable=nAvailable, criterion=settings$nextItemCriterion, method=settings$scoringMethod, randomesque=randomesque, D=d, cbGroup=NULL, cbControl=NULL))
          } else {
            stop(ex)
          }
        })
        nextItemsIndices = c(nextItemsIndices, result$item)
      }
    }
  } else {
    #linear
    foundNonEmptyPage = F
    while(!foundNonEmptyPage) {
      pageFirstItemIndex = (nextPage - 1) * as.numeric(settings$itemsPerPage) + 1
      pageLastItemIndex = min(nextPage * as.numeric(settings$itemsPerPage), dim(items)[1])
      if(pageLastItemIndex < pageFirstItemIndex) { break }

      nextItemsIndices = pageFirstItemIndex:pageLastItemIndex
      if(!is.null(excludedItems)) {
        if(length(nextItemsIndices) > 1) {
          excludedIndices = which(nextItemsIndices %in% excludedItems)
          if(length(excludedIndices) > 0) {
            nextItemsIndices = nextItemsIndices[-excludedIndices]
          }
        } else {
          if(length(nextItemsIndices) == 1 && nextItemsIndices %in% excludedItems) { nextItemsIndices = NULL }
        }
      }
      if(length(nextItemsIndices) == 0) { 
        nextPage = nextPage + 1 
      } else {
        foundNonEmptyPage = T
      }
    }      
  }

  if(!is.na(settings$nextItemModule) && settings$nextItemModule != "") {
    nextItemsIndices = concerto.test.run(settings$nextItemModule, params=list(
      nextItemsIndices=nextItemsIndices,
      settings = settings,
      theta = theta,
      traitScores = traitScores,
      itemsAdministered=itemsAdministered,
      excludedItems=excludedItems,
      cbGroup=cbGroup,
      cbControl=cbControl,
      session=session,
      items=items,
      nextPage=nextPage,
      prevPage=prevPage,
      direction=direction
    ))$nextItemsIndices
  }
}

.branch = "continue"
concerto.log(nextItemsIndices, "nextItemsIndices")

if(length(nextItemsIndices) > 0) {
  nextItems = items[nextItemsIndices,]
  concerto.log(nextItems, "next items")
  nextItemsSafe = getSafeItems(nextItems, settings$itemBankTableExtraFields)
  responsesSafe = getSafePastResponses(nextItems, nextItemsIndices)
  resumedItemsIds = NULL
} else {
  .branch = "stop"
  concerto.log("empty set of next items - stopping")
}
