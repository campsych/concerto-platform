concerto.table.query <-
function(sql, params=list()){
  sql <- gsub("^\\s+|\\s+$", "", sql)
  sql <- gsub("^[[:space:]]*", "", sql)
  sql <- concerto.table.insertParams(sql, params)

  result <- NULL
  output <- NULL
  sql <- trimws(sql)
  if(toupper(substring(sql, 1, 6)) == "SELECT") {
    result <- dbSendQuery(concerto$connection, sql)
    output <- fetch(result, -1)
    if(concerto$driver == "pdo_mysql") {
        while(dbMoreResults(concerto$connection)) {
           next_result <- dbNextResult(concerto$connection)
           dbClearResult(next_result)
        }
      }
  } else if(toupper(substring(sql, 1, 6)) == "INSERT") {
    if(concerto$driver == "pdo_sqlsrv") {
         result <- dbSendQuery(concerto$connection, paste0(sql,"; SELECT SCOPE_IDENTITY();"))
         output <- fetch(result, n=-1)[1,1]
         concerto$sqlsrv_last_insert_id <<- output
    } else {
        result <- dbSendStatement(concerto$connection, sql)
        output <- dbGetRowsAffected(result)
    }
  } else {
    result <- dbSendStatement(concerto$connection, sql)
    output <- dbGetRowsAffected(result)
  }
  dbClearResult(result)

  return(output)
}
