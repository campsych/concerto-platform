concerto.table.lastInsertId <-
function(){
  if(concerto$driver == "pdo_sqlsrv") {
    res = dbSendQuery(concerto$connection, "SELECT SCOPE_IDENTITY();")
    id = fetch(res, n=-1)[1,1]
    dbClearResult(res)
    return(id)
  } else {
    return(dbGetQuery(concerto$connection, "SELECT SCOPE_IDENTITY();")[1,1])
  }
}
