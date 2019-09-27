concerto.template.redirect = function(url) {
    concerto.template.show(
        html=paste0("<script>location.href='",url,"'</script>"),
        skipOnResume=T
    )
}