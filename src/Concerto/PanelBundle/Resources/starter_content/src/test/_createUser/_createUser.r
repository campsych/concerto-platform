library(digest)

formatFields = function(login, password, userBankEncryption, enabled, extraFields) {
  fields = list(
    login=login,
    password=password,
    enabled=enabled
  )
  if(userBankEncryption != "plain") {
    fields$password = digest(password, userBankEncryption, serialize=F)
  }
  if(is.list(extraFields)) {
    for(name in ls(extraFields)) {
      fields[[name]] = extraFields[[name]]
    }
  }
  return(fields)
}

checkLoginExist = function(login, tableMap) {
  sql = "
SELECT * FROM {{table}} 
WHERE {{loginColumn}}='{{login}}'
"
  user = concerto.table.query(sql, params=list(
    table=tableMap$table,
    loginColumn=tableMap$columns$login,
    login=login
  ))

  return(dim(user)[1]>0)
}

getMappedColumns = function(fieldNames, tableMap) {
  cols = c()
  for(i in 1:length(fieldNames)) {
    col = tableMap$columns[[fieldNames[i]]]
    if(!is.null(col)) {
      cols=c(cols,col)
      next
    }
    cols=c(cols,fieldNames[i])
  }
  return(cols)
}

insertUser = function(fields, tableMap) {
  sql = paste0(
    "INSERT INTO {{table}} (",
    paste(getMappedColumns(ls(fields), tableMap), collapse=","),
    ") VALUES (",
    paste0("'{{",ls(fields),"}}'", collapse=","),
    ")"
  )
  concerto.table.query(sql, params=append(fields, list(
    table=tableMap$table
  )))
  userId = concerto.table.lastInsertId()
  concerto.log(userId, title="new user id")

  sql="SELECT * FROM {{table}} WHERE {{idColumn}}={{id}}"
  user=concerto.table.query(sql,params=list(
    table=tableMap$table,
    idColumn=tableMap$columns$id,
    id=userId
  ))
  concerto.log(user, title="inserted user")
  return(user)
}

user=NULL
if(is.na(password)) { password = "" }
tableMap = fromJSON(userBankTable)
fields = formatFields(login, password, userBankEncryption, enabled, extraFields)
if(checkLoginExist(login, tableMap)) {
  concerto.log(login, title="login already exist")
  .branch = "loginAlreadyExist"
} else {
  concerto.log(login, title="login doesn't exist and can be created")
  user=insertUser(fields, tableMap)
  .branch = "created"
}
