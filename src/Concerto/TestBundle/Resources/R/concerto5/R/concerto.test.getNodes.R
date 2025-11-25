concerto.test.getNodes = function(testId){
  idField <- "flowTest_id"
  testId <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("
    SELECT
    id AS \"id\",
    type AS \"type\",
    sourceTest_id AS \"sourceTest_id\"
    FROM TestNode
    WHERE %s='%s'
  ",idField,testId))
  response <- fetch(result,n=-1)

  return(response)
}
