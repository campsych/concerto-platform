updateScores = function(templateResponse, currentItems, currentItemsIndices, scores) {
  for(i in 1:dim(currentItems)[1]) {
    item = currentItems[i,]
    index = currentItemsIndices[i]
    itemResponse = templateResponse[[paste0("r",item$id)]]
    responseOptions = fromJSON(item$responseOptions)
    score = responseOptions$defaultScore

    if(!is.null(itemResponse)) {
      if(length(responseOptions$scoreMap) > 0) {
        for(i in 1:length(responseOptions$scoreMap)) {
          sm = responseOptions$scoreMap[[i]]
          if(sm$value == itemResponse) {
            score = sm$score
            break
          }
        }
      }
    }

    scores[index] = as.numeric(score)
  }
  return(scores)
}

updateTraitScores = function(scores, items) {
  traitScores = list()
  for(i in 1:length(scores)) {
    score = as.numeric(scores[[i]])
    if(is.null(score)) { score = 0 }
    item = as.list(items[i,])
    if(is.null(traitScores[[item$trait]])) {
      traitScores[[item$trait]] = 0
    }
    traitScores[[item$trait]] = traitScores[[item$trait]] + score
  }
  return(traitScores)
}

scores = updateScores(response, currentItems, currentItemsIndices, scores)
traitScores = updateTraitScores(scores, items)

concerto.log(scores, "scores")
concerto.log(traitScores, "traitScores")
