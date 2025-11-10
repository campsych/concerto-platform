concerto.test.getConnections = function(testId){
  idField <- "flowTest_id"
  testId <- dbEscapeStrings(concerto$connection,toString(testId))
  result <- dbSendQuery(concerto$connection,sprintf("
    SELECT
    id AS \"id\",
    sourceNode_id AS \"sourceNode_id\",
    sourcePort_id AS \"sourcePort_id\",
    destinationNode_id AS \"destinationNode_id\",
    destinationPort_id AS \"destinationPort_id\",
    returnFunction AS \"returnFunction\"
    FROM TestNodeConnection
    WHERE %s='%s'
  ",idField,testId))
  response <- fetch(result,n=-1)

  return(response)
}
