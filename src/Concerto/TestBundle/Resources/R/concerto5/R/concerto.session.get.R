concerto.session.get = function(sessionHash){
    response = concerto.table.query("
        SELECT
        id AS \"id\",
        test_id AS \"test_id\",
        timeLimit AS \"timeLimit\",
        status AS \"status\",
        params AS \"params\",
        error AS \"error\",
        clientIp AS \"clientIp\",
        clientBrowser AS \"clientBrowser\",
        submitterPort AS \"submitterPort\",
        hash AS \"hash\"
        FROM TestSession
        WHERE hash='{{sessionHash}}'
    ", list(sessionHash=sessionHash))
    return(response)
}
