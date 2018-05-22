concerto.session.serialize <- function(){
    concerto.log("serializing session...")

    serialized = serialize(concerto, NULL)
    save("concerto", file=concerto$sessionFile, safe=F)

    concerto.log("session serialized")
}