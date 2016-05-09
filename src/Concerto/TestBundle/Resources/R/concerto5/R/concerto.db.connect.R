concerto.db.connect = function(driver, username, password, dbname, host, unix_socket, port){
  con = NULL
  if(driver=="pdo_mysql"){
    require("RMySQL")
    con <- dbConnect(
      MySQL(),
      username=username,
      password=password,
      dbname=dbname,
      host=host,
      unix.socket=unix_socket,
      port=as.numeric(port))
    dbSendQuery(con, statement = 'SET NAMES \"utf8\";')
  } else if (driver=="pdo_sqlite"){
    require("RSQLite")
    stop("pdo_sqlite driver not implemented yet")
  } else if (driver=="pdo_pgsql"){
    require("RPostgreSQL")
    stop("pdo_pgsql driver not implemented yet")
  } else if (driver=="pdo_sqlsrv"){
    require("rClr")
    require("rsqlserver")
    con <- dbConnect(
      SqlServer(),url=paste("User Id=",username,";Password=",password,";Database=",dbname,";Server=",host,";MultipleActiveResultSets=true",sep=''))
  } else if (driver=="oci8" || driver=="pdo_oci"){
    require("ROracle")
    stop("oci8 and pdo_oci driver not implemented yet")
  } else if (driver=="sqlanywhere"){
    stop("sqlanywhere driver not supported yet")
  }
  
  if(!existsFunction("dbEscapeStrings")) {
      dbEscapeStrings <- function(con,string){
          return(gsub("'","''",string))
      }
  }
  return(con)
}