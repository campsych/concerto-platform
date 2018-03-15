concerto.table.lastInsertId <-
function(){
  id = NULL
  if(concerto$connectionParams$driver == "pdo_sqlsrv") {
    id = concerto$sqlsrv_last_insert_id
  } else {
    id = dbGetQuery(concerto$connection, "SELECT LAST_INSERT_ID();")[1,1]
  }
  return(id)
}
