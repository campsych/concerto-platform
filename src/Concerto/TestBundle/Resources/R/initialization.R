require(concerto5)

options(encoding='UTF-8')

SOURCE_CLIENT = 0
SOURCE_PROCESS = 1
SOURCE_SERVER = 2

RESPONSE_VIEW_TEMPLATE = 0
RESPONSE_FINISHED = 1
RESPONSE_SUBMIT = 2
RESPONSE_SERIALIZE = 3
RESPONSE_SERIALIZATION_FINISHED = 4
RESPONSE_VIEW_FINAL_TEMPLATE = 5
RESPONSE_UNRESUMABLE = 11
RESPONSE_ERROR = -1

STATUS_RUNNING = 0
STATUS_SERIALIZED = 1
STATUS_FINALIZED = 2
STATUS_ERROR = 3

concerto <- list()
concerto$workingDir <- commandArgs(TRUE)[6]
concerto$sessionFile <- paste(concerto$workingDir,"session.Rs",sep="")
concerto$publicDir <- commandArgs(TRUE)[7]
concerto$mediaUrl <- commandArgs(TRUE)[8]

r_server <- fromJSON(commandArgs(TRUE)[2])
concerto$r_server.host <- r_server$host
concerto$r_server.port <- r_server$port
submitter <- fromJSON(commandArgs(TRUE)[3])
concerto$submitter.host <- submitter$host
concerto$submitter.port <- submitter$port

dbEscapeStrings <- function(con,string){
    return(gsub("'","''",string))
}

connection <- fromJSON(commandArgs(TRUE)[1])
concerto$connection <- concerto5:::concerto.db.connect(connection$driver, connection$username, connection$password, connection$dbname, connection$host, connection$unix_socket, connection$port)
rm(connection)

concerto$session <- as.list(concerto5:::concerto.session.get(commandArgs(TRUE)[5]))
concerto$session$previousStatus <- concerto$session$status
concerto$session$status <- STATUS_RUNNING
concerto$session$params <- fromJSON(concerto$session$params)

concerto$flow <- list()

returns <<- list()
tryCatch({
    setwd(concerto$workingDir)

    returns <<- concerto.test.run(concerto$session["test_id"], concerto$session$params, TRUE)

}, error = function(e) {
    if(concerto$session$status == STATUS_RUNNING){
      print(e)
      response = RESPONSE_ERROR
      if(e$message == "session unresumable") {
        response = RESPONSE_UNRESUMABLE
      }
      concerto5:::concerto.server.respond(response)
      concerto$session$error <<- e
      concerto$session$status <<- STATUS_ERROR
      concerto5:::concerto.session.update()
      stop("Error executing test logic.")
    }
})

if(concerto$session$status == STATUS_FINALIZED){
    concerto5:::concerto.session.finalize(RESPONSE_VIEW_FINAL_TEMPLATE, returns)
} else if(concerto$session$status == STATUS_RUNNING){
    concerto5:::concerto.session.finalize(RESPONSE_FINISHED, returns)
}