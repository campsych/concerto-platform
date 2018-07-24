concerto.session.update = function(){
  concerto.log("updating session...")

  sql = sprintf("UPDATE TestSession SET
    status = '%s',
    timeLimit = '%s',
    error = '%s'
    WHERE id='%s'",
  dbEscapeStrings(concerto$connection, toString(concerto$session$status)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$timeLimit)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$error)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$id)))

  res = dbSendStatement(concerto$connection, statement = sql)
  dbClearResult(res)
}
