concerto.db.connect = function(driver, username, password, dbname, host, unix_socket, port, servicename, charset){
    concerto.log(paste0("connecting with db using ",driver))
    con = NULL
    if (driver == "pdo_mysql") {
        require("RMySQL")
        con <- dbConnect(
            MySQL(),
            username = username,
            password = password,
            dbname = dbname,
            host = host,
            unix.socket = unix_socket,
            port = as.numeric(port)
        )
        dbExecute(con, statement = 'SET NAMES \"utf8\"')
    } else if (driver == "pdo_sqlite") {
        #require("RSQLite")
        stop("pdo_sqlite driver not implemented yet")
    } else if (driver == "pdo_pgsql") {
        #require("RPostgreSQL")
        stop("pdo_pgsql driver not implemented yet")
    } else if (driver == "pdo_sqlsrv") {
        require("RSQLServer")
        con <- dbConnect(
            RSQLServer::SQLServer(),
            server = host,
            database = dbname,
            port = port,
            properties = list(user = username, password = password)
        )
    } else if (driver == "oci8") {
        require("ROracle")
        conStr <- paste0("(DESCRIPTION=(ADDRESS=(PROTOCOL=tcp)(HOST=", host, ")(PORT=", port, "))(CONNECT_DATA=(SERVICE_NAME=", servicename, ")))")
        con <- dbConnect(
            Oracle(),
            username = username,
            password = password,
            dbname = conStr,
        )
        dbExecute(con, statement = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'")
        dbExecute(con, statement = "ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'")
    } else if (driver == "sqlanywhere") {
        stop("sqlanywhere driver not supported yet")
    }

    if (! existsFunction("dbEscapeStrings")) {
        dbEscapeStrings <<- function(con, string){
            return(gsub("'", "''", string))
        }
    }
    if (! existsFunction("dbSendStatement")) {
        dbSendStatement <<- dbSendQuery
    }
    return(con)
}