getZ = function() {
  return((as.numeric(rawScore) - as.numeric(mean)) / as.numeric(sd))
}

getT = function(z) {
  return(z*10+50)
}

getStanine = function(z) {
  stanine = round(z*2+5)
  stanine = min(max(stanine, 1), 9)
  return(stanine)
}

getSten = function(z) {
  sten = round(z*2+5.5)
  sten = min(max(sten, 1), 10)
  return(sten)
}

getIq = function(z) {
  return(z*15+100)
}

getPercentile = function(z) {
  return(pnorm(z)*100)
}

getPercentileRange = function() {
  ranges = NULL
  score = NULL
  if(scoreType == "percentileRanges") {
    rangesList = fromJSON(percentileRanges)
    if(length(rangesList) > 0) {
      for(i in 1:length(rangesList)) {
        ranges = rbind(ranges, data.frame(rangesList[[i]], stringsAsFactors=F))
      }
    }
  }
  if(scoreType == "percentileRangesTable") {
    rangesTable = fromJSON(percentileRangesTable)
    sql = "SELECT 
{{lowerBoundColumn}} AS lowerBound,
{{upperBoundColumn}} AS upperBound,
{{scoreColumn}} AS score
FROM {{table}}"
    ranges = concerto.table.query(sql, params=list(
      lowerBoundColumn=rangesTable$columns$lowerBound,
      upperBoundColumn=rangesTable$columns$upperBound,
      scoreColumn=rangesTable$columns$score,
      table=rangesTable$table
    ))
  }

  if(dim(ranges)[1] > 0) {
    for(i in 1:dim(ranges)[1]) {
      range = ranges[i,]
      range$lowerBound = as.numeric(range$lowerBound)
      range$upperBound = as.numeric(range$upperBound)
      if(!is.na(range$lowerBound) && range$lowerBound > as.numeric(rawScore)) { next }
      if(!is.na(range$upperBound) && range$upperBound <= as.numeric(rawScore)) { next }
      score = as.numeric(range$score)
    }
  }
  
  return(score)
}

score = switch(scoreType, 
               "zScore" = getZ(),
               "tScore" = getT(getZ()),
               "stanine" = getStanine(getZ()),
               "sten" = getSten(getZ()),
               "iq" = getIq(getZ()),
               "percentile" = getPercentile(getZ()),
               "percentileRanges" = getPercentileRange(),
               "percentileRangesTable" = getPercentileRange(),
               "custom" = eval(parse(text=customScoreFormula)),
               "rawScore" = as.numeric(rawScore),
               as.numeric(rawScore)
              )

feedback = NULL
if(feedbackType != "none") {
  ranges = NULL
  if(feedbackType == "ranges") {
    rangesList = fromJSON(feedbackRanges)
    if(length(rangesList) > 0) {
      for(i in 1:length(rangesList)) {
        ranges = rbind(ranges, data.frame(rangesList[[i]], stringsAsFactors=F))
      }
    }
  }
  if(feedbackType == "rangesTable") {
    rangesTable = fromJSON(feedbackRangesTable)
    sql = "SELECT 
{{lowerBoundColumn}} AS lowerBound,
{{upperBoundColumn}} AS upperBound,
{{feedbackColumn}} AS feedback
FROM {{table}}"
    ranges = concerto.table.query(sql, params=list(
      lowerBoundColumn=rangesTable$columns$lowerBound,
      upperBoundColumn=rangesTable$columns$upperBound,
      feedbackColumn=rangesTable$columns$feedback,
      table=rangesTable$table
    ))
  }

  if(!is.null(ranges) && dim(ranges)[1] > 0) {
    for(i in 1:dim(ranges)[1]) {
      range = ranges[i,]
      range$lowerBound = as.numeric(range$lowerBound)
      range$upperBound = as.numeric(range$upperBound)
      if(!is.na(range$lowerBound) && range$lowerBound > score) { next }
      if(!is.na(range$upperBound) && range$upperBound <= score) { next }
      feedback = range$feedback
    }
  }
}