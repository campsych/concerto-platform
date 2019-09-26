concerto.session.getResumeUrl = function(){
    url = paste0(concerto$platformUrl, "test/session/", concerto$session$hash)
    return(url)
}