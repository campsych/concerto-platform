concerto.test.get = function(testId, cache=F, includeSubObjects=F){

  test = concerto$cache$tests[[as.character(testId)]]
  if(!is.null(test)) {
    if(includeSubObjects && is.null(test$variables)) {
      test$variables = concerto5:::concerto.test.getVariables(test$id)
      if(test$type == 2) {
        test$nodes <- concerto5:::concerto.test.getNodes(test$id)
        test$connections <- concerto5:::concerto.test.getConnections(test$id)
        test$ports <- concerto5:::concerto.test.getPorts(test$id)
      }
    }
    return(test)
  }

  idField <- "id"
  if(is.character(testId)){
    idField <- "name"
  }

  testID <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("
  SELECT
  id,
  name,
  code,
  type,
  sourceWizard_id
  FROM Test
  WHERE %s='%s'
  ",idField,testId))
  response <- fetch(result,n=-1)

  if(dim(response)[1] > 0) {
    test = as.list(response)
    if(includeSubObjects && is.null(test$variables)) {
      test$variables <- concerto5:::concerto.test.getVariables(test$id)
      if(test$type == 1) {
        result = dbSendQuery(concerto$connection, paste0("
        SELECT test_id FROM TestWizard WHERE id=",dbEscapeStrings(concerto$connection,toString(test$sourceWizard_id)),"
        "))
        sourceTestId = fetch(result,n=-1)
        test$sourceTest <- concerto.test.get(sourceTestId, cache, includeSubObjects)
      }
      if(test$type == 2) {
        test$nodes <- concerto5:::concerto.test.getNodes(test$id)
        test$connections <- concerto5:::concerto.test.getConnections(test$id)
        test$ports <- concerto5:::concerto.test.getPorts(test$id)
      }
    }

    if(cache) {
        concerto$cache$tests[[as.character(test$id)]] <<- test
        concerto$cache$tests[[test$name]] <<- test
    }
  }

  return(test)
}
