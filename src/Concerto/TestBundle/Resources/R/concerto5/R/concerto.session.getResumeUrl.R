concerto.session.getResumeUrl = function(){
    url = paste0(concerto$appUrl, "/test/session/", concerto$session$hash)
    return(url)
}