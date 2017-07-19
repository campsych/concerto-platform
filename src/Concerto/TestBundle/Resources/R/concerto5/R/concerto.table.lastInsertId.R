concerto.table.lastInsertId <-
function(){
  if(concerto$driver == "pdo_sqlsrv") {
    return(dbGetQuery(concerto$connection, "SELECT SCOPE_IDENTITY();")[1,1])
  } else {
    return(dbGetQuery(concerto$connection, "SELECT LAST_INSERT_ID();")[1,1])
  }
}
