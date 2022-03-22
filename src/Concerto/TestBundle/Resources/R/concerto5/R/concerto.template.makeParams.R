concerto.template.makeParams = function(params=list()){
  finalParams = concerto$globalTemplateParams
  for(name in ls(params)) {
    if(is.null(params[[name]])) {
      finalParams[[name]] = list(NULL)
    } else {
      finalParams[[name]] = params[[name]]
    }
  }
  return(finalParams)
}
