concerto.test.get = function(testId, includeSubObjects=F){

  if(!is.null(concerto$cache$tests[[as.character(testId)]])) {
    return(concerto$cache$tests[[as.character(testId)]])
  }

  idField <- "id"
  if(is.character(testId)){
    idField <- "name"
  }

  testID <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name,code,type FROM Test WHERE %s='%s'",idField,testId))
  response <- fetch(result,n=-1)

  test = NULL
  if(dim(response)[1] > 0) {
    test = as.list(response)
    if(includeSubObjects && is.null(test$variables)) {
      test$variables = concerto5:::concerto.test.getVariables(test$id)
      if(test$type == 2) {
        test$nodes <- concerto5:::concerto.test.getNodes(test$id)
        test$connections <- concerto5:::concerto.test.getConnections(test$id)
        test$ports <- concerto5:::concerto.test.getPorts(test$id)
      }
    }

    concerto$cache$tests[[as.character(response$id)]] <<- test
    concerto$cache$tests[[response$name]] <<- test
  }

  return(test)
}
