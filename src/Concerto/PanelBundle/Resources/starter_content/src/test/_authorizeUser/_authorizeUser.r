library(digest)

resultCode = 0

getColumnMap = function() {
  map = list(
    table=NULL,
    columns=list(
      login="login",
      password="password",
      enabled="enabled"
    )
  )
  
  if(userBankType == "table") {
    map = fromJSON(userBankTable)
  }
  
  return(map)
}

getUserByLogin = function(login, columnMap) {
  if(userBankType == "direct") {
    userList = fromJSON(userBankList)
    if(length(userList) == 0) {
      resultCode <<- 1
      return(NULL)
    }

    for(i in 1:length(userList)) {
      user = userList[[i]]
      if(user$login == login && user$enabled == 1) {
        return(user)
      }
    }
    resultCode <<- 1
    return(NULL)
  } else {
    sql = "
SELECT * 
FROM {{table}} 
WHERE 
{{loginColumn}}='{{login}}'
AND {{enabledColumn}}=1
"
    user = concerto.table.query(sql, params=list(
      table=columnMap$table,
      loginColumn=columnMap$columns$login,
      enabledColumn=columnMap$columns$enabled,
      login=login
    ))
    if(dim(user)[1] == 0) {
      resultCode <<- 1
      return(NULL)
    }
    return(as.list(user[1,]))
  }
}

checkPassword = function(rawPassword, encryptedPassword, encryption) {
  if(is.na(encryptedPassword) || encryptedPassword == "") { return(T) }
  if(is.na(rawPassword)) { return(F) }
  if(encryption=="plain") {
    return(rawPassword == encryptedPassword)
  }
  return(digest(rawPassword, encryption, serialize=F) == encryptedPassword)
}

authorizeUser = function(login, password, columnMap) {
  user = getUserByLogin(login, columnMap)
  concerto.log(login, "login checked")
  concerto.log(password, "password checked")
  concerto.log(user, "user checked for password")

  if(!is.null(user) && checkPassword(password, user[[columnMap$columns$password]], userBankEncryption)) {
    return(user)
  }
  resultCode <<- 1
  return(NULL)
}

columnMap = getColumnMap()
user = authorizeUser(login, password, columnMap)
if(is.null(user)) {
  concerto.log(paste0("user ",login," unauthorized"), title="authorization result")
  .branch = "unauthorized"
} else {
  concerto.log(paste0("user ",login," authorized"), title="authorization result")
  .branch = "authorized"
}
