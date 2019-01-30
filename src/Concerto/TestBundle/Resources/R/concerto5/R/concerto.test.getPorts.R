concerto.test.getPorts = function(testId){
  
  idField <- "flowTest_id"
  testId <- dbEscapeStrings(concerto$connection,toString(testId))

  result <- dbSendQuery(concerto$connection,sprintf("
  SELECT
  TestNodePort.id AS id,
  node_id,
  variable_id,
  TestNodePort.value AS value,
  TestNodePort.type AS type,
  TestNodePort.name AS name,
  string,
  dynamic,
  pointer,
  pointerVariable
  FROM TestNodePort 
  LEFT JOIN TestNode ON TestNode.id = TestNodePort.node_id
  WHERE %s='%s'
  ",idField,testId))

  response <- fetch(result,n=-1)

  return(response)
}
