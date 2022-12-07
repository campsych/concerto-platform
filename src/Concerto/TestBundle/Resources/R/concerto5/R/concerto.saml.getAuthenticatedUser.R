concerto.saml.getAuthenticatedUser = function(){
    concerto.log("concerto.saml.getAuthenticatedUser")
    hash = concerto$lastResponse$cookies$concertoSamlTokenHash
    concerto.log(hash, "hash")

    if(!is.null(hash)) {
        idResult = concerto.table.query("
                SELECT max(id) AS id
                FROM SamlToken
                WHERE hash='{{hash}}' AND
                revoked = 0 AND
                (expiresAt IS NULL OR expiresAt > UNIX_TIMESTAMP())
            ", list(
            hash=hash
        ))
        if(dim(idResult)[1] == 0) { return(NULL) }
        id = idResult$id

        token = concerto.table.query("SELECT * FROM SamlToken WHERE id='{{id}}'", list(
            id=id
        ))
        concerto.log(token, "token")
        if(dim(token)[1] == 0) { return(NULL) }
        return(fromJSON(token$attributes))
    }
    return(NULL)
}