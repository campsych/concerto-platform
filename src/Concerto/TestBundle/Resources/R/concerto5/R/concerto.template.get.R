concerto.template.get = function(templateId){
  idField <- "id"
  if(is.character(templateId)){
    idField <- "name"
  }
  templateId <- dbEscapeStrings(concerto$connection,toString(templateId))
    
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name,head,html FROM ViewTemplate WHERE %s='%s'",idField,templateId))
  response <- fetch(result,n=-1)
  return(response)
}
