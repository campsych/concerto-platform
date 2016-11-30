concerto.template.insertParams = function(html,params=list(),removeMissing=T){
  matches <- unlist(regmatches(html,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",html)))
  offset = 0
  while(length(matches)>offset){
    index <- 1
    while(index<=length(matches)){
      value <- gsub("\\{\\{", "", matches[index])
      value <- gsub("\\}\\}", "", value)
      if(!is.null(params[[value]])){
        html <- gsub(matches[index], toString(params[[value]]), html, fixed=TRUE)
      }
      else {
        if(removeMissing) {
            html <- gsub(matches[index], "", html, fixed=TRUE)
        } else {
            offset=offset+1
        }
      }
      index=index+1
    }
    matches <- unlist(regmatches(html,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",html)))
  }
  return(html)
}
