concerto.table.get <-
function(tableId){

  objField <- "id"
  if(is.character(tableId)){
    objField <- "name"
  }

  tableId <- dbEscapeStrings(concerto$connection,toString(tableId))
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name FROM Table WHERE %s='%s'",objField,tableId))
  response <- fetch(result,n=-1)
  return(response)
}
