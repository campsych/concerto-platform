concerto.session.update = function(returns=list()){
  print("updating session...")

  if(concerto$session$template_id == 0 || !is.null(concerto$session$template_id) && is.na(concerto$session$template_id) || is.null(concerto$session$template_id)) concerto$session$template_id <<- "NULL"
  sql = sprintf("UPDATE TestSession SET 
    templateHead = '%s',
    templateHtml = '%s',
    status = '%s',
    template_id = %s,
    timeLimit = '%s',
    rServerNodePort = '%s',
    returns = '%s',
    error = '%s'
    WHERE id='%s'",
    dbEscapeStrings(concerto$connection, toString(concerto$session$templateHead)),
    dbEscapeStrings(concerto$connection, toString(concerto$session$templateHtml)),
    dbEscapeStrings(concerto$connection, toString(concerto$session$status)),
    dbEscapeStrings(concerto$connection, toString(concerto$session$template_id)),
    dbEscapeStrings(concerto$connection, toString(concerto$session$timeLimit)),
    dbEscapeStrings(concerto$connection, toString(concerto$r_server.port)),
    dbEscapeStrings(concerto$connection, toString(toJSON(returns))),
    dbEscapeStrings(concerto$connection, toString(concerto$session$error)),
    dbEscapeStrings(concerto$connection, toString(concerto$session$id)))

  dbSendQuery(concerto$connection, statement = sql)
}
