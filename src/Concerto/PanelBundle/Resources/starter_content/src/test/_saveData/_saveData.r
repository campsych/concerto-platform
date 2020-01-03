getSessionId = function() {
  id = 0
  if(!is.null(session) && is.list(session)) {
    id = session$id
  } else {
    id = paste0("i",concerto$session$id)
  }
  return(id)
}

getMergedData = function() {
  mergedData = data
  if(!is.list(mergedData)) { 
    mergedData = list()
  }

  for(.name in .dynamicInputs) {
    mergedData[[.name]] = get(.name)
  }
  return(mergedData)
}

getDataId = function(name, sessionId, decodedTable) {
  sql = "SELECT id FROM {{table}} WHERE {{sessionIdColumn}}='{{sessionId}}' AND {{nameColumn}}='{{name}}'"
  result = concerto.table.query(sql, params=list(
    table=decodedTable$table,
    sessionIdColumn=decodedTable$columns$session_id,
    sessionId=sessionId,
    nameColumn=decodedTable$columns$name,
    name=name
  ))
  if(dim(result)[1] == 0) { return(NULL) }
  return(result[1,1])
}

saveData = function(name, value, sessionId, decodedTable) {
  id = getDataId(name, sessionId, decodedTable)
  params=list(
    table=decodedTable$table,
    sessionIdColumn=decodedTable$columns$session_id,
    sessionId=sessionId,
    nameColumn=decodedTable$columns$name,
    name=name,
    valueColumn=decodedTable$columns$value,
    value=value,
    id=id
  )

  if(is.null(id)) {
    concerto.table.query("INSERT INTO {{table}} ({{sessionIdColumn}}, {{nameColumn}}, {{valueColumn}}) VALUES ('{{sessionId}}', '{{name}}', '{{value}}')", params=params)
  } else {
    concerto.table.query("UPDATE {{table}} SET {{sessionIdColumn}}='{{sessionId}}', {{nameColumn}}='{{name}}', {{valueColumn}}='{{value}}' WHERE id={{id}}", params=params)
  }
}

sessionId = getSessionId()
decodedTable = fromJSON(table)
mergedData = getMergedData()
for(name in ls(mergedData)) {
  saveData(name, mergedData[[name]], sessionId, decodedTable)
}