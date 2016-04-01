concerto.test.get = function(testId){

  idField <- "id"
  if(is.character(testId)){
    idField <- "name"
  }

  testID <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name,code,type,resumable FROM Test WHERE %s='%s'",idField,testId))
  response <- fetch(result,n=-1)
  return(response)
}
