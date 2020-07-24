library(catR)

isSkipped = function(item) {
  skippedTemplateResponse = templateResponse[[paste0("skip",item$id)]]
  if(item$skippable == 1 && !is.null(skippedTemplateResponse) && skippedTemplateResponse == 1) {
    return(T)
  }
  return(F)
}

getScore = function(item, response) {
  defaultScore = if(is.null(response) || is.na(response)) { NA } else { 0 }

  if(!is.null(response) && !is.na(response) && !is.null(item$responseOptions) && item$responseOptions != "") {
    responseOptions = fromJSON(item$responseOptions)
    defaultScore = responseOptions$defaultScore
    if(is.null(defaultScore)) { defaultScore = NA }
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

getTraits = function(item, value) {
  traitList = NULL

  #item level traits
  itemTrait = trimws(item$trait)
  if(!is.na(itemTrait) && length(itemTrait) > 0 && itemTrait != "") {
    traits = strsplit(itemTrait,",")[[1]]
    if(length(traits) > 0) {
      for(j in 1:length(traits)) {
        trait = trimws(traits[j])
        if(trait == "") { next }
        traitList = unique(c(traitList, trait))
      }
    }
  }

  #response level trait
  if(!is.null(value) && !is.na(value) && !is.na(item$responseOptions) && !is.null(item$responseOptions) && item$responseOptions != "") {
    responseOptions = fromJSON(item$responseOptions)
    if(length(responseOptions$scoreMap) > 0) {
      for(j in 1:length(responseOptions$scoreMap)) {
        sm = responseOptions$scoreMap[[j]]
        if(sm$value == value) {
          responseTrait = trimws(sm$trait)

          if(!is.na(responseTrait) && length(responseTrait) > 0 && responseTrait != "") {
            traits = strsplit(responseTrait,",")[[1]]
            if(length(traits) > 0) {
              for(k in 1:length(traits)) {
                trait = trimws(traits[k])
                if(trait == "") { next }
                traitList = unique(c(traitList, trait))
              }
            }
          }
          break
        }
      }
    }
  }

  if(length(traitList) == 0) { return(NULL) }
  return(paste(sort(traitList), collapse=","))
}

calculateTraitScores = function(itemsAdministered, responses, scores) {
  traitScores = list()
  for(i in 1:length(itemsAdministered)) {
    itemIndex = itemsAdministered[i]
    value = responses[i]
    item = items[itemIndex,]
    score = scores[i]
    if(is.na(score)) { score = 0 }
    traitList = getTraits(item, value)

    #sum scores
    if(length(traitList) > 0) {
      for(j in 1:length(traitList)) {
        trait = traitList[j]
        if(trait == "") { next }
        if(is.null(traitScores[[trait]])) { traitScores[[trait]] = 0 }
        traitScores[[trait]] = traitScores[[trait]] + score
      }
    }
  }
  return(traitScores)
}

getItemsTraits = function(itemsAdministered) {
  traits = list()
  if(length(itemsAdministered) > 0) {
    for(i in 1:length(itemsAdministered)) {
      itemIndex = itemsAdministered[i]
      value = responses[i]
      item = items[itemIndex,]
      itemTraits = getTraits(item, value)
      traits[[i]] = itemTraits
    }
  }
  return(traits)
}

calculateTheta = function(trait, itemsAdministered, responses, scores, itemTraits) {
  d = as.numeric(settings$d)
  selectedScores = NULL
  selectedParamBank = NULL

  for(i in 1:length(itemsAdministered)) {
    itemIndex = itemsAdministered[i]
    traits = itemTraits[i]
    if(is.null(trait) || trait %in% traits) {
      validParams = !is.na(paramBank[itemIndex,1])
      if(validParams) {
        selectedScores = c(selectedScores, scores[i])
        selectedParamBank = rbind(selectedParamBank, paramBank[itemIndex,])
      }
    }
  }

  if(is.null(selectedParamBank)) { return(NULL) } 
  if(all(is.na(selectedScores))) { return(NULL) }
  theta = thetaEst(matrix(selectedParamBank, ncol=dim(paramBank)[2], byrow=F), selectedScores, model=settings$model, method=settings$scoringMethod, D=d)
  return(theta)
}

calculateSem = function(trait, theta, itemsAdministered, responses, scores, itemTraits) {
  d = as.numeric(settings$d)
  selectedScores = NULL
  selectedParamBank = NULL

  for(i in 1:length(itemsAdministered)) {
    itemIndex = itemsAdministered[i]
    traits = itemTraits[i]
    if(is.null(trait) || trait %in% traits) {
      validParams = !is.na(paramBank[itemIndex,1])
      if(validParams) {
        selectedScores = c(selectedScores, scores[i])
        selectedParamBank = rbind(selectedParamBank, paramBank[itemIndex,])
      }
    }
  }

  if(is.null(selectedParamBank)) { return(NULL) } 
  if(all(is.na(selectedScores))) { return(NULL) }
  sem = semTheta(theta, matrix(selectedParamBank, ncol=dim(paramBank)[2], byrow=F), selectedScores, model=settings$model, method=settings$scoringMethod, D=d)
  return(sem)
}

currentScores = NULL
currentTraits = NULL
prevSem = sem
prevTheta = theta
traitTheta = list()
traitSem = list()

itemsAdministered = unique(c(itemsAdministered, itemsIndices))
itemTraits = getItemsTraits(itemsAdministered)
allTraits = unlist(unique(itemTraits))
for(i in 1:length(itemsIndices)) {
  item = items[itemsIndices[i],]
  responseRaw = templateResponse[[paste0("r",item$id)]]
  if(is.null(responseRaw)) { responseRaw = NA }
  skipped = isSkipped(item)

  score = NA
  if(!skipped) { 
    score = getScore(item, responseRaw)
  }

  index = which(itemsAdministered == itemsIndices[i])
  scores[index] = score
  responses[index] = responseRaw

  currentScores = c(currentScores, score)
  currentTraits = c(currentTraits, getTraits(item, responseRaw))
}

shouldCalculateTheta = !is.na(settings$calculateTheta) && settings$calculateTheta == 1
shouldCalculateSem = !is.na(settings$calculateSem) && settings$calculateSem == 1
if(length(allTraits) > 0) {
  for(i in 1:length(allTraits)) {
    trait = allTraits[i]

    if(shouldCalculateTheta) {
      if(!trait %in% ls(traitTheta)) {
        traitTheta[[trait]] = as.numeric(settings$startingTheta)
      }
      newTheta = calculateTheta(trait, itemsAdministered, responses, scores, itemTraits)
      if(!is.null(newTheta)) {
        traitTheta[[trait]] = newTheta
      }
    }

    if(shouldCalculateSem) {
      if(!trait %in% ls(traitSem)) {
        traitSem[[trait]] = 1
      }
      newSem = calculateSem(trait, traitTheta[[trait]], itemsAdministered, responses, scores, itemTraits)
      if(!is.null(newSem)) {
        traitSem[[trait]] = newSem
      }
    }
  }
}

if(shouldCalculateTheta) {
  newTheta = calculateTheta(NULL, itemsAdministered, responses, scores, itemTraits)
  if(!is.null(newTheta)) {
    theta = newTheta
  }
}
if(shouldCalculateSem) {
  newSem = calculateSem(NULL, theta, itemsAdministered, responses, scores, itemTraits)
  if(!is.null(newSem)) {
    sem = newSem
  }
}

traitScores = calculateTraitScores(itemsAdministered, responses, scores)

concerto.log(theta, "theta")
concerto.log(sem, "sem")
concerto.log(traitScores, "traitScores")
concerto.log(traitTheta, "traitTheta")
concerto.log(traitSem, "traitTheta")