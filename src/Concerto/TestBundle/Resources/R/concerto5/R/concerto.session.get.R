concerto.session.get = function(sessionHash){
  sessionHash <- dbEscapeStrings(concerto$connection,toString(sessionHash))
  result <- dbSendQuery(concerto$connection,sprintf("SELECT 
                                                    id, 
                                                    test_id,
                                                    template_id,
                                                    templateHead,
                                                    templateHtml,
                                                    timeLimit,
                                                    status,
                                                    params,
                                                    error,
                                                    clientIp,
                                                    clientBrowser,
                                                    submitterPort,
                                                    hash
                                                    FROM TestSession WHERE hash='%s'",sessionHash))
  response <- fetch(result,n=-1)
  return(response)
}
