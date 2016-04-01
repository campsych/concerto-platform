concerto.template.insertParams = function(html,params=list()){
  matches <- unlist(regmatches(html,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",html)))
  matches <- matches[!matches == '{{timeLeft}}'] 
  while(length(matches)>0){
    index <- 1
    while(index<=length(matches)){
      value <- gsub("\\{\\{","",matches[index])
      value <- gsub("\\}\\}","",value)
      if(!is.null(params[[value]])){
        html <- gsub(matches[index],toString(params[[value]]),html, fixed=TRUE)
      }
      else {
        html <- gsub(matches[index],"",html, fixed=TRUE)
      }
      index=index+1
    }
    matches <- unlist(regmatches(html,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",html)))
    matches <- matches[!matches == '{{timeLeft}}'] 
  }
  return(html)
}
