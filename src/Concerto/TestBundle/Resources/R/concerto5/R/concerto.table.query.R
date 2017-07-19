concerto.table.query <-
function(sql, params=list(), forceResultSet=F){
  sql <- gsub("^\\s+|\\s+$", "", sql)
  sql <- gsub("^[[:space:]]*", "", sql)
  sql <- concerto.table.insertParams(sql, params)

  result <- NULL
  output <- NULL
  if(startsWith(toupper(sql), "SELECT")) {
    result <- dbSendQuery(concerto$connection, sql)
    output <- fetch(result, -1)
    if(concerto$driver == "pdo_mysql") {
        while(dbMoreResults(concerto$connection)) {
           next_result <- dbNextResult(concerto$connection)
           dbClearResult(next_result)
        }
      }
  } else {
    result <- dbSendStatement(concerto$connection, sql)
    output <- dbGetRowsAffected(result)
  }
  dbClearResult(result)

  return(output)
}
