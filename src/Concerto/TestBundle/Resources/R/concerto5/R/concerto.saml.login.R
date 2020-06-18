concerto.saml.login = function(redirectTo=NULL){
    if(is.null(redirectTo)) redirectTo = concerto.session.getResumeUrl()
    url = paste0(concerto$appUrl, "/api/saml/login?redirectTo=", redirectTo)
    concerto.template.redirect(url)
}