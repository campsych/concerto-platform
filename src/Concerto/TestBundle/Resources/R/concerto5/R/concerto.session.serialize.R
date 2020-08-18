concerto.session.serialize <- function(){
    concerto.log("serializing session...")

    serialized = serialize(concerto, NULL)

    if(concerto$sessionStorage == "redis") {
        expSeconds = as.numeric(concerto$sessionFilesExpiration) * 24 * 60 * 60
        concerto$redisConnection$SETEX(concerto$session$hash, expSeconds, serialized)
    } else {
        writeBin(serialized, concerto$sessionFile)
    }

    concerto.log("session serialized")
}