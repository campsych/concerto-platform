concerto.table.lastInsertId <-
function(connection = NULL, tableName = NULL){
  id = NULL
  if(is.null(connection)) { connection = concerto$connection }
  if(concerto$dbConnectionParams$driver == "pdo_sqlsrv") {
    id = concerto$sqlsrv_last_insert_id
  } else if(concerto$dbConnectionParams$driver == "oci8") {
    if(is.null(tableName)) {
        stop("oci8 driver requires tableName to be passed to concerto.table.lastInsertId")
    }

    sql = paste0("SELECT ", tableName, "_SEQ.CURRVAL AS \"id\" FROM dual")
    id = dbGetQuery(connection, sql)[1,1]
  } else {
    id = dbGetQuery(connection, "SELECT LAST_INSERT_ID();")[1,1]
  }
  return(id)
}
