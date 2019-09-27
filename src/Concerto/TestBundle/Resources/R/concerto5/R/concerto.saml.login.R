concerto.saml.login = function(redirectTo=NULL){
    if(is.null(redirectTo)) redirectTo = concerto.session.getResumeUrl()
    url = paste0(concerto$platformUrl, "api/saml/login?redirectTo=", redirectTo)
    concerto.template.redirect(url)
}