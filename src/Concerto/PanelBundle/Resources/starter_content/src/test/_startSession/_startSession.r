concerto.log(user, "user")
if(is.na(test_id) || test_id == "") {
  test_id = concerto$mainTest$id
}

formatFields = function(user, extraFields) {
  userId = 0
  if(is.list(user)) { userId=user$id }
  fields = list(
    user_id=userId,
    internal_id=concerto$session$id, 
    test_id=test_id,
    finished=0
  )
  if(is.list(extraFields)) {
    for(name in ls(extraFields)) {
      fields[[name]] = extraFields[[name]]
    }
  }
  return(fields)
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

insertSession = function(fields, tableMap) {
  startedTimeColumn = tableMap$columns$startedTime
  updateTimeColumn = tableMap$columns$updateTime
  
  sqlColumns = paste(getMappedColumns(ls(fields), tableMap), collapse=",")
  sqlValues = paste0("'{{",ls(fields),"}}'", collapse=",")
  sql = paste0("
    INSERT INTO {{table}}
    ({{startedTimeColumn}}, {{updateTimeColumn}}, ",sqlColumns,")
    VALUES (CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ",sqlValues,")
  ")
  concerto.table.query(sql, params=append(fields, list(
    table=tableMap$table,
    startedTimeColumn=startedTimeColumn,
    updateTimeColumn=updateTimeColumn
  )))
  id = concerto.table.lastInsertId(concerto$connection, tableMap$table)

  sql = NULL
  otherColumnsSql = ""
  if(concerto$dbConnectionParams$driver == "oci8") {
    allColumns = concerto.table.query("SELECT column_name \"col\" FROM user_tab_columns WHERE table_name = UPPER('{{table}}')", list(table=tableMap$table))[,"col"]
    explicitColumns = toupper(c(
      tableMap$columns$id,
      tableMap$columns$internal_id,
      tableMap$columns$test_id,
      tableMap$columns$user_id,
      tableMap$columns$startedTime,
      tableMap$columns$updateTime,
      tableMap$columns$finished
    ))
    otherColumnsSql = paste(setdiff(allColumns, explicitColumns), collapse=",")
    if(otherColumnsSql != "") { otherColumnsSql = paste0(",", otherColumnsSql) }
    sql = "
        SELECT
        {{idColumn}} AS \"id\",
        {{internalIdColumn}} AS \"internal_id\",
        {{testIdColumn}} AS \"test_id\",
        {{userIdColumn}} AS \"user_id\",
        {{startedTimeColumn}} AS \"startedTime\",
        {{updateTimeColumn}} AS \"updateTime\",
        {{finishedColumn}} AS \"finished\"
        {{otherColumnsSql}}
        FROM {{table}}
        WHERE id='{{id}}'
    "
  } else {
    sql = "SELECT * FROM {{table}} WHERE {{idColumn}}='{{id}}'"
  }
  session = concerto.table.query(sql, params=list(
    table=tableMap$table,
    idColumn=tableMap$columns$id,
    internalIdColumn=tableMap$columns$internal_id,
    testIdColumn=tableMap$columns$test_id,
    userIdColumn=tableMap$columns$user_id,
    startedTimeColumn=tableMap$columns$startedTime,
    updateTimeColumn=tableMap$columns$updateTime,
    finishedColumn=tableMap$columns$finished,
    id=id,
    otherColumnsSql=otherColumnsSql
  ))
  if(dim(session)[1] > 0) {
    return(session[1,])
  }
  return(NULL)
}

resumeSession = function(user, tableMap) {
  if(is.null(user)) { return(NULL) }

  sql = NULL
  otherColumnsSql = ""
  if(concerto$dbConnectionParams$driver == "oci8") {
    allColumns = concerto.table.query("SELECT column_name \"col\" FROM user_tab_columns WHERE table_name = UPPER('{{table}}')", list(table=tableMap$table))[,"col"]
    explicitColumns = toupper(c(
      tableMap$columns$id,
      tableMap$columns$internal_id,
      tableMap$columns$test_id,
      tableMap$columns$user_id,
      tableMap$columns$startedTime,
      tableMap$columns$updateTime,
      tableMap$columns$finished
    ))
    otherColumnsSql = paste(setdiff(allColumns, explicitColumns), collapse=",")
    if(otherColumnsSql != "") { otherColumnsSql = paste0(",", otherColumnsSql) }
    sql = "
      SELECT
      {{idColumn}} AS \"id\",
      {{internalIdColumn}} AS \"internal_id\",
      {{testIdColumn}} AS \"test_id\",
      {{userIdColumn}} AS \"user_id\",
      {{startedTimeColumn}} AS \"startedTime\",
      {{updateTimeColumn}} AS \"updateTime\",
      {{finishedColumn}} AS \"finished\"
      {{otherColumnsSql}}
      FROM {{table}}
      WHERE
      {{testIdColumn}} = '{{testId}}' AND
      {{userIdColumn}} = '{{userId}}' AND
      {{finishedColumn}} = '0'
      ORDER BY {{idColumn}} DESC
    "
  } else {
    sql = "
      SELECT * FROM {{table}}
      WHERE
      {{testIdColumn}} = '{{testId}}' AND
      {{userIdColumn}} = '{{userId}}' AND
      {{finishedColumn}} = '0'
      ORDER BY {{idColumn}} DESC
    "
  }

  session = concerto.table.query(sql, params=list(
  table=tableMap$table,
  idColumn=tableMap$columns$id,
  internalIdColumn=tableMap$columns$internal_id,
  testIdColumn=tableMap$columns$test_id, 
  testId=test_id, 
  userIdColumn=tableMap$columns$user_id, 
  userId=user$id,
  startedTimeColumn=tableMap$columns$startedTime,
  updateTimeColumn=tableMap$columns$updateTime,
  finishedColumn=tableMap$columns$finished,
  otherColumnsSql=otherColumnsSql
), n=1)
  if(dim(session)[1] == 0) {
    return(NULL)
  }

  session = as.list(session)
  session$previousInternal_id = session$internal_id
  session$internal_id = concerto$session$id

  timeLimit = as.numeric(resumableExpiration)
  if(timeLimit > 0) {
    timeDiff = as.numeric(Sys.time()) - as.numeric(strptime(session$updateTime, "%Y-%m-%d %H:%M:%S"))
    if(timeDiff > timeLimit) {
      concerto.log("session resume time limit exceeded")
      return(NULL)
    }
  }

  concerto.table.query("
    UPDATE {{table}}
    SET
    {{internalIdColumn}}='{{internal_id}}',
    {{updateTimeColumn}}=CURRENT_TIMESTAMP
    WHERE {{idColumn}}='{{id}}'
  ", params=list(
  table=tableMap$table,
  idColumn=tableMap$columns$id,
  internalIdColumn=tableMap$columns$internal_id,
  internal_id=concerto$session$id,
  id=session$id,
  updateTimeColumn=tableMap$columns$updateTime
))

  return(session)
}

fields = formatFields(user, extraFields)
concerto.log(fields, "fields")
tableMap = fromJSON(sessionBankTable)

session = NULL
if(resumable == 1) {
  session = resumeSession(user, tableMap)
  if(restoreState == 1 && !is.null(session)) {
    hash = concerto.table.query("SELECT hash AS \"hash\" FROM TestSession WHERE id='{{id}}'", list(id=session$previousInternal_id))
    concerto.log(hash, "resuming session...")
    if(!concerto.session.unserialize(hash=hash)) {
      session = NULL
    }
  }
}
if(is.null(session)) {
  session = insertSession(fields, tableMap)
}

if(preventParallelSessionUsage == 1) {
  concerto.event.add("onTemplateSubmit", function(response) {
    sql = "
      SELECT {{internalIdCol}} AS \"internal_id\"
      FROM {{table}}
      WHERE {{idColumn}}='{{id}}'
    "
    internalId = concerto.table.query(sql, params=list(
      table=tableMap$table,
      idColumn=tableMap$columns$id,
      internalIdCol=tableMap$columns$internal_id,
      id=session$id
    ))[1,1]
    
    if(internalId != concerto$session$id) {
      concerto.log("detected parallel session usage")
      concerto.session.stop(response=RESPONSE_SESSION_LOST)
    }
  })
}

concerto.log(session, "session")