concerto.test.getVariables = function(testId){
  idField <- "test_id"
  testId <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("
    SELECT
    id AS \"id\",
    name AS \"name\",
    value AS \"value\",
    type AS \"type\"
    FROM TestVariable
    WHERE %s='%s'
  ",idField,testId))
  response <- fetch(result,n=-1)

  return(response)
}
