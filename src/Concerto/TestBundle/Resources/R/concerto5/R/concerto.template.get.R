concerto.template.get = function(templateId){

  if(!is.null(concerto$cache$templates[[as.character(templateId)]])) {
    return(concerto$cache$templates[[as.character(templateId)]])
  }

  idField <- "id"
  if(is.character(templateId)){
    idField <- "name"
  }
  templateId <- dbEscapeStrings(concerto$connection,toString(templateId))
    
  result <- dbSendQuery(concerto$connection,sprintf("SELECT id,name,head,html,css,js FROM ViewTemplate WHERE %s='%s'",idField,templateId))
  response <- fetch(result,n=-1)

  template = NULL
  if(dim(response)[1] > 0){
    template = as.list(response)
    concerto$cache$templates[[as.character(response$id)]] <<- response
    concerto$cache$templates[[response$name]] <<- response
  }

  return(template)
}
