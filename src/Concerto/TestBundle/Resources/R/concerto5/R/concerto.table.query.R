concerto.table.query <-
function(sql, params=list()){
  sql <- gsub("^\\s+|\\s+$", "", sql)
  sql <- gsub("^[[:space:]]*", "", sql)
  sql <- concerto.table.insertParams(sql, params)
  result <- dbSendQuery(concerto$connection, sql)
  records <- NULL
  try({
    records <- fetch(result, -1)
  }, silent=TRUE)
  try({
    dbClearResult(result)
  }, silent=TRUE)
  return(records)
}
