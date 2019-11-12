library(catR)

getSafeItem = function(item, extraFields) {
  item = as.list(item)
  item$correct = NULL

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
  item$responseOptions$options = orderedOptions
  item$responseOptions = toJSON(item$responseOptions)

  extraFields = fromJSON(extraFields)
  for(extraField in extraFields) {
    if(extraField$sensitive == 1) {
      item[[extraField$name]] = NULL
    }
  }

  return(item)
}

currentIndex = length(itemsAdministered) + 1
itemsNum = dim(items)[1]
nextItemIndex = resumedItemIndex

if(nextItemIndex == 0) {
  itemsExcluded = itemsAdministered
  if(itemsNum > 0) {
    for(i in 1:itemsNum) {
      fixedIndex = items[i,"fixedIndex"]
      if(!is.na(fixedIndex) && !is.null(fixedIndex) && fixedIndex != "" && fixedIndex != 0){
        concerto.log(fixedIndex, "fixedIndex")
        if(fixedIndex == currentIndex) {
          nextItemIndex = i
        } else {
          itemsExcluded = unique(c(itemsExcluded, i))
        }
      }
    }
  }

  cbGroup = NULL
  cbControl = NULL
  cbProps = fromJSON(settings$contentBalancing)
  concerto.log(cbProps, "cbProps")
  if(length(cbProps) > 0) {
    cbGroup = as.character(items[,"cbGroup"])
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

  if(nextItemIndex == 0) {
    result = nextItem(paramBank, model=settings$model, theta=theta, out=itemsExcluded, criterion=settings$nextItemCriterion, method=settings$scoringMethod, randomesque=settings$nextItemRandomesque, cbGroup=cbGroup, cbControl=cbControl)
    nextItemIndex = result$item
  }

  if(!is.na(settings$nextItemModule) && settings$nextItemModule != "") {
    nextItemIndex = concerto.test.run(settings$nextItemModule, params=list(
      nextItemIndex=nextItemIndex,
      settings = settings,
      theta = theta,
      itemsAdministered=itemsAdministered,
      itemsExcluded=itemsExcluded,
      cbGroup=cbGroup,
      cbControl=cbControl,
      session=session,
      items=items
    ))$nextItemIndex
  }
}

concerto.log(nextItemIndex, "nextItemIndex")
nextItem = items[nextItemIndex,]
concerto.log(nextItem, "nextItem")
nextItemSafe = getSafeItem(nextItem, settings$itemBankTableExtraFields)
resumedItemIndex = 0

if(settings$sessionResuming == 1) {
  sessionTable = fromJSON(settings$sessionTable)
  concerto.table.query("
UPDATE {{table}} 
SET {{nextItemIdCol}}={{nextItem_id}} 
WHERE id={{id}}", params=list(
  table = sessionTable$table,
  nextItemIdCol = sessionTable$columns$nextItem_id,
  nextItem_id = nextItem$id,
  id = session$id
))
}