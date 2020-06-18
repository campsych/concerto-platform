concerto.saml.logout = function(redirectTo=NULL){
    if(is.null(redirectTo)) redirectTo = concerto.session.getResumeUrl()
    url = paste0(concerto$appUrl, "/api/saml/logout?redirectTo=", redirectTo)
    concerto.template.redirect(url)
}