concerto.session.serialize <- function(){
    concerto.log("serializing session...")

    if(concerto$sessionStorage == "redis") {
        #TODO add comppression
        serialized = serialize(concerto, NULL)
        expSeconds = as.numeric(concerto$sessionFilesExpiration) * 24 * 60 * 60
        concerto$redisConnection$SETEX(concerto$session$hash, expSeconds, serialized)
    } else {
        save(concerto, file=concerto$sessionFile)
    }

    concerto.log("session serialized")
}