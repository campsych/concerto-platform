concerto.table.query <-
function(sql, params=list(), n=-1){
  sql <- gsub("^\\s+|\\s+$", "", sql)
  sql <- concerto.table.insertParams(sql, params)

  concerto.log(sql)

  result <- NULL
  output <- NULL
  if(toupper(substring(sql, 1, 6)) == "SELECT") {
    result <- dbSendQuery(concerto$connection, sql)
    output <- fetch(result, n=n)
  } else if(toupper(substring(sql, 1, 6)) == "INSERT") {
    if(concerto$connectionParams$driver == "pdo_sqlsrv") {
         result <- dbSendQuery(concerto$connection, paste0(sql,"; SELECT SCOPE_IDENTITY();"))
         output <- fetch(result, n=1)[1,1]
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
