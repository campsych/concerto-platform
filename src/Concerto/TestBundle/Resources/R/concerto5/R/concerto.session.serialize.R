concerto.session.serialize <- function(){
    concerto.log("serializing session...")

    serialized = serialize(concerto, NULL)
    writeBin(serialized, concerto$sessionFile)

    concerto.log("session serialized")
}