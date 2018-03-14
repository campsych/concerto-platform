concerto.table.get <-
function(tableId){

  if(!is.null(concerto$cache$tables[[as.character(tableId)]])) {
    return(concerto$cache$tables[[as.character(tableId)]])
  }

  objField <- "id"
  if(is.character(tableId)){
    objField <- "name"
  }

  tableId <- dbEscapeStrings(concerto$connection,toString(tableId))
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name FROM Table WHERE %s='%s'",objField,tableId))
  response <- fetch(result,n=-1)

  table = NULL
  if(dim(response)[1] > 0){
    table = as.list(response)
    concerto$cache$tables[[as.character(response$id)]] <<- response
    concerto$cache$tables[[response$name]] <<- response
  }

  return(table)
}
