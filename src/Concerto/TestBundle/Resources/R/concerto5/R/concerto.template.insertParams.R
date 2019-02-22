concerto.template.insertParams = function(html,params=list(),removeMissing=T){
  insertRegex = "\\{\\{[^(\\}\\})|(\\{\\{)]*\\}\\}"
  matches <- unlist(regmatches(html,gregexpr(insertRegex,html)))
  offset = 0
  while(length(matches)>offset){
    index <- 1
    while(index<=length(matches)){
      value <- gsub("\\{\\{", "", matches[index])
      value <- gsub("\\}\\}", "", value)
      if(substring(value, 1, 9) == "template:") {
        insert = concerto.template.join(templateId=substring(value,10), params=params)
        if(Sys.info()['sysname'] == "Windows") {
          if(Encoding(insert) == "UTF-8") { insert = enc2native(insert) }
        }
        html <- gsub(matches[index], insert, html, fixed=TRUE)
      } else if(substring(value, 1, 6) == "trans:") {
        insert = c.trans(substring(value,7))
        if(Sys.info()['sysname'] == "Windows") {
          if(Encoding(insert) == "UTF-8") { insert = enc2native(insert) }
        }
        html <- gsub(matches[index], insert, html, fixed=TRUE)
      } else if(!is.null(params[[value]])){
        insert = as.character(params[[value]])
        if(Sys.info()['sysname'] == "Windows") {
            if(Encoding(insert) == "UTF-8") { insert = enc2native(insert) }
        }
        html <- gsub(matches[index], insert, html, fixed=TRUE)
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
    matches <- unlist(regmatches(html,gregexpr(insertRegex,html)))
  }
  return(html)
}
