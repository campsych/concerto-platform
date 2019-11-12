library(catR)

getScore = function(item, response) {
  defaultScore = 0
  if(!is.null(response)) {
    responseOptions = fromJSON(item$responseOptions)
    defaultScore = responseOptions$defaultScore
    if(length(responseOptions$scoreMap) > 0) {
      for(i in 1:length(responseOptions$scoreMap)) {
        sm = responseOptions$scoreMap[[i]]
        if(sm$value == response) {
          defaultScore = sm$score
          break
        }
      }
    }
  }

  score = as.numeric(defaultScore)
  if(!is.na(settings$responseScoreModule) && settings$responseScoreModule != "") {
    score = concerto.test.run(settings$responseScoreModule, params=list(
      item=item,
      response=response,
      score=score,
      settings=settings
    ))$score
  }
  return(as.numeric(score))
}

responseRaw = templateResponse[[paste0("r",item$id)]]
score = getScore(item, responseRaw)
scores = c(scores, score)
itemsAdministered = unique(c(itemsAdministered, itemIndex))
paramBankAdministered = paramBank
if(dim(items)[1] > 1) {
  paramBankAdministered = paramBank[itemsAdministered,]
}
concerto.log(itemsAdministered, "itemsAdministered")
concerto.log(scores, "scores")
theta <- thetaEst(matrix(paramBankAdministered, ncol=as.numeric(settings$itemParamsNum), byrow=F), scores, model=settings$model, method=settings$scoringMethod)
concerto.log(theta, "theta")
sem <- semTheta(theta, matrix(paramBankAdministered, ncol=as.numeric(settings$itemParamsNum), byrow=F), scores, model=settings$model)
concerto.log(sem, "SEM")
