getItems = function(itemBankType, itemBankItems, itemBankTable, order){
  items = NULL
  if(itemBankType == "table") {
    tableMap = fromJSON(itemBankTable)
    table = tableMap$table
    questionColumn = tableMap$columns$question
    responseOptionsColumn = tableMap$columns$responseOptions
    orderIndexColumn = tableMap$columns$orderIndex
    traitColumn = tableMap$columns$trait
    correctColumn = tableMap$columns$correct
    items = concerto.table.query(
      "
SELECT 
id, 
{{questionColumn}} AS question, 
{{responseOptionsColumn}} AS responseOptions,
{{orderIndexColumn}} AS orderIndex,
{{traitColumn}} AS trait,
{{correctColumn}} AS correct
FROM {{table}} 
ORDER BY {{orderIndexColumn}}
", 
      list(
        questionColumn=questionColumn,
        responseOptionsColumn=responseOptionsColumn,
        orderIndexColumn=orderIndexColumn,
        traitColumn=traitColumn,
        correctColumn=correctColumn,
        table=table
      ))
  }
  if(itemBankType == "direct") {
    itemBankItems = fromJSON(itemBankItems)
    if(length(itemBankItems) > 0) {
      for(i in 1:length(itemBankItems)) {
        itemBankItems[[i]]$responseOptions = as.character(toJSON(itemBankItems[[i]]$responseOptions)) #response options don't fit into flat table, so turn them back to JSON.
        items = rbind(items, data.frame(itemBankItems[[i]], stringsAsFactors=F))
      }
    }
  } 
  
  if(dim(items)[1] == 0) { stop("Item bank must not be empty!") }
  if(order == "random") {
    items = items[sample(1:dim(items)[1]),]
  }
  return(items)
}

items = getItems(settings$itemBankType, settings$itemBankItems, settings$itemBankTable, settings$order)

itemsAdministered = NULL
scores = NULL
traitScores = list()

testTimeStarted = as.numeric(Sys.time())
testTimeLeft = as.numeric(settings$testTimeLimit) + as.numeric(settings$testTimeLimitOffset)
