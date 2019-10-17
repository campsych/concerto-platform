getItems = function(itemBankType, itemBankItems, itemBankTable, extraFields){
  items = NULL
  if(itemBankType == "table") {
    tableMap = fromJSON(itemBankTable)
    table = tableMap$table
    questionColumn = tableMap$columns$question
    responseOptionsColumn = tableMap$columns$responseOptions
    p1Column = tableMap$columns$p1
    p2Column = tableMap$columns$p2
    p3Column = tableMap$columns$p3
    p4Column = tableMap$columns$p4
    p5Column = tableMap$columns$p5
    p6Column = tableMap$columns$p6
    p7Column = tableMap$columns$p7
    p8Column = tableMap$columns$p8
    p9Column = tableMap$columns$p9
    cbGroupColumn = tableMap$columns$cbGroup
    fixedIndexColumn = tableMap$columns$fixedIndex

    extraFields = fromJSON(extraFields)
    extraFieldsNames = lapply(extraFields, function(extraField) { return(extraField$name) })
    extraFieldsSql = paste(extraFieldsNames, collapse=", ")  
    if(extraFieldsSql != "") { extraFieldsSql = paste0(", ", extraFieldsSql) }

    items = concerto.table.query(
      "
SELECT 
id, 
{{questionColumn}} AS question, 
{{responseOptionsColumn}} AS responseOptions,
{{p1Column}} AS p1,
{{p2Column}} AS p2,
{{p3Column}} AS p3,
{{p4Column}} AS p4,
{{p5Column}} AS p5,
{{p6Column}} AS p6,
{{p7Column}} AS p7,
{{p8Column}} AS p8,
{{p9Column}} AS p9,
{{cbGroupColumn}} AS cbGroup,
{{fixedIndexColumn}} AS fixedIndex
{{extraFields}}
FROM {{table}}
", 
      list(
        questionColumn=questionColumn,
        responseOptionsColumn=responseOptionsColumn,
        p1Column=p1Column,
        p2Column=p2Column,
        p3Column=p3Column,
        p4Column=p4Column,
        p5Column=p5Column,
        p6Column=p6Column,
        p7Column=p7Column,
        p8Column=p8Column,
        p9Column=p9Column,
        cbGroupColumn=cbGroupColumn,
        fixedIndexColumn=fixedIndexColumn,
        extraFields=extraFieldsSql,
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

  if(!is.na(settings$itemBankFilterModule) && settings$itemBankFilterModule != "") {
    items = concerto.test.run(settings$itemBankFilterModule, params=list(
      settings = settings,
      session=session,
      items=items
    ))$items
  }

  if(dim(items)[1] == 0) { stop("Item bank must not be empty!") }
  return(items)
}

items = getItems(settings$itemBankType, settings$itemBankItems, settings$itemBankTable, settings$itemBankTableExtraFields)
itemsNum = dim(items)[1]

paramBank = items[,c("p1","p2","p3","p4","p5","p6","p7","p8","p9"),drop=F]
paramBank = paramBank[,1:as.numeric(settings$itemParamsNum),drop=F]
paramBank = apply(paramBank, 2, as.numeric)
if(is.vector(paramBank)) { 
  paramBank = rbind(paramBank)
}

theta = as.numeric(settings$startingTheta)
itemsAdministered = NULL
correctness = NULL
testTimeStarted = as.numeric(Sys.time())
