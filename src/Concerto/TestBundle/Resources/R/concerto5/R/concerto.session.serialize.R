concerto.session.serialize <- function(){
    concerto.log(concerto$sessionFile, "serializing session...")

    serialized = serialize(concerto, NULL)

    if(concerto$sessionStorage == "redis") {
        concerto$redisConnection$SET(concerto$session$hash, serialized)
    } else {
        writeBin(serialized, concerto$sessionFile)
    }

    concerto.log("session serialized")
}