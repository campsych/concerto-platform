concerto.table.insertParams <-
function(sql,params=list()){
  matches <- unlist(regmatches(sql,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",sql)))
  while(length(matches)>0){
    index <- 1
    while(index<=length(matches)){
      name <- gsub("\\{\\{","",matches[index])
      name <- gsub("\\}\\}","",name)
      if(!is.null(params[[name]])){
        value <- dbEscapeStrings(concerto$connection,toString(params[[name]]))
        sql <- gsub(matches[index],value,sql, fixed=TRUE)
      }
      else {
        sql <- gsub(matches[index],"",sql, fixed=TRUE)
      }
      index=index+1
    }
    matches <- unlist(regmatches(sql,gregexpr("\\{\\{[^\\}\\}]*\\}\\}",sql)))
  }
  return(sql)
}
