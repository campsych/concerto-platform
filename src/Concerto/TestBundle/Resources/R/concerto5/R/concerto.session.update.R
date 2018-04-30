concerto.session.update = function(){
  concerto.log("updating session...")

  if(concerto$session$template_id == 0 || !is.null(concerto$session$template_id) && is.na(concerto$session$template_id) || is.null(concerto$session$template_id)) concerto$session$template_id <<- "NULL"
  if(concerto$session$loader_id == 0 || !is.null(concerto$session$loader_id) && is.na(concerto$session$loader_id) || is.null(concerto$session$loader_id)) concerto$session$loader_id <<- "NULL"
  sql = sprintf("UPDATE TestSession SET 
    templateHead = '%s',
    templateCss = '%s',
    templateJs = '%s',
    templateHtml = '%s',
    templateParams = '%s',
    status = '%s',
    template_id = %s,
    loader_id = %s,
    loaderHead = '%s',
    loaderCss = '%s',
    loaderJs = '%s',
    loaderHtml = '%s',
    timeLimit = %s,
    error = '%s'
    WHERE id='%s'",
  dbEscapeStrings(concerto$connection, toString(concerto$session$templateHead)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$templateCss)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$templateJs)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$templateHtml)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$templateParams)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$status)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$template_id)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$loader_id)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$loaderHead)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$loaderCss)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$loaderJs)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$loaderHtml)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$timeLimit)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$error)),
  dbEscapeStrings(concerto$connection, toString(concerto$session$id)))

  res = dbSendStatement(concerto$connection, statement = sql)
  dbClearResult(res)
}
