concerto.test.getPorts = function(testId){
  
  idField <- "flowTest_id"
  testId <- dbEscapeStrings(concerto$connection,toString(testId))

  result <- dbSendQuery(concerto$connection,sprintf("
  SELECT TestNodePort.id AS id, node_id, variable_id, TestNodePort.value AS value, TestVariable.type AS type, name, string 
  FROM TestNodePort 
  LEFT JOIN TestNode ON TestNode.id = TestNodePort.node_id
  LEFT JOIN TestVariable ON TestVariable.id = TestNodePort.variable_id
  WHERE %s='%s'",idField,testId))

  response <- fetch(result,n=-1)

  return(response)
}
