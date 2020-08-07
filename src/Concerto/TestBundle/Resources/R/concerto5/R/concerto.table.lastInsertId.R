concerto.table.lastInsertId <-
function(connection = NULL){
  id = NULL
  if(is.null(connection)) { connection = concerto$connection }
  if(concerto$dbConnectionParams$driver == "pdo_sqlsrv") {
    id = concerto$sqlsrv_last_insert_id
  } else {
    id = dbGetQuery(connection, "SELECT LAST_INSERT_ID();")[1,1]
  }
  return(id)
}
