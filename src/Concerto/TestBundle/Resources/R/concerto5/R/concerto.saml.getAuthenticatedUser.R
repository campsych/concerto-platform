concerto.saml.getAuthenticatedUser = function(){
    hash = concerto$lastResponse$cookies$concertoSamlTokenHash
    if(!is.null(hash)) {
        idResult = concerto.table.query("
            SELECT max(id) AS \"id\"
            FROM SamlToken
            WHERE
            hash='{{hash}}' AND
            revoked = 0 AND
            (expiresAt IS NULL OR expiresAt > (CAST(CURRENT_TIMESTAMP AS DATE) - DATE '1970-01-01') * 86400)
        ", list(hash=hash))
        if(dim(idResult)[1] == 0) { return(NULL) }
        id = idResult$id

        token = concerto.table.query("
            SELECT
            id AS \"id\",
            attributes AS \"attributes\",
            nameId AS \"nameId\",
            hash AS \"hash\",
            expiresAt AS \"expiresAt\",
            revoked AS \"revoked\"
            FROM SamlToken
            WHERE id='{{id}}'
        ", list(id=id))
        if(dim(token)[1] == 0) { return(NULL) }
        return(fromJSON(token$attributes))
    }
    return(NULL)
}