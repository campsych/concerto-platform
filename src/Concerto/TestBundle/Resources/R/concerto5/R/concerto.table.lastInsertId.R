concerto.table.lastInsertId <-
function(){
  if(concerto$driver == "pdo_sqlsrv") {
    return(dbGetQuery(concerto$connection, "SELECT SCOPE_IDENTITY();")[1,1])
  } else {
    res = dbSendQuery(concerto$connection, "SELECT LAST_INSERT_ID();")
    id = fetch(res, n=-1)[1,1]
    clean = dbClearResult(res)
    return(id)
  }
}
