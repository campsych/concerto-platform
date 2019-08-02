library(digest)

resultCode = 0
getUserByLogin = function(login) {
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
    tableMap = fromJSON(userBankTable)
    sql = "
SELECT * 
FROM {{table}} 
WHERE 
{{loginColumn}}='{{login}}'
AND {{enabledColumn}}=1
"
    user = concerto.table.query(sql, params=list(
      table=tableMap$table,
      loginColumn=tableMap$columns$login,
      enabledColumn=tableMap$columns$enabled,
      login=login
    ))
    if(dim(user)[1] == 0) {
      resultCode <<- 1
      return(NULL)
    }
    return(user[1,])
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

authorizeUser = function(login, password) {
  user = getUserByLogin(login)
  concerto.log(login, "login checked")
  concerto.log(password, "password checked")
  concerto.log(user, "user checked for password")
  
  if(!is.null(user) && checkPassword(password, user$password, userBankEncryption)) {
    return(user)
  }
  resultCode <<- 1
  return(NULL)
}

user = authorizeUser(login, password)
if(is.null(user)) {
  concerto.log(paste0("user ",login," unauthorized"), title="authorization result")
  .branch = "unauthorized"
} else {
  concerto.log(paste0("user ",login," authorized"), title="authorization result")
  .branch = "authorized"
}
