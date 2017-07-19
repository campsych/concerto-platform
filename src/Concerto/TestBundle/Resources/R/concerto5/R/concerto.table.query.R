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
  if(concerto$driver == "pdo_mysql") {
      while(dbMoreResults(concerto$connection)) {
        next_result <- dbNextResult(concerto$connection)
        try({
            dbClearResult(next_result)
        }, silent=TRUE)
      }
  }
  return(records)
}
