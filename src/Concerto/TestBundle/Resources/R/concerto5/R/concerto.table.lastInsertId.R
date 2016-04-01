concerto.table.lastInsertId <-
function(){
  return(dbGetQuery(concerto$connection, "SELECT LAST_INSERT_ID();")[1,1])
}
