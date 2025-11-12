getExtraFieldsSql = function() {
  sql = ""
  if(is.list(extraFields)) {
    for(name in ls(extraFields)) {
      value = extraFields[[name]]
      if(!is.null(value) && !is.na(value)) {
        sql = paste0(sql, ", {{name}}='{{value}}'")
        sql = concerto.table.insertParams(sql, params=list(
          name=name,
          value=value
        ))
      }
    }
  }
  return(sql)
}

if(is.list(extraFields)) {
  for(name in ls(extraFields)) {
    session[[name]] = extraFields[[name]]
  }
}

sessionBank = fromJSON(sessionBankTable)
params=list(
  table=sessionBank$table,
  finishedColumn=sessionBank$columns$finished,
  updateTimeColumn=sessionBank$columns$updateTime,
  id=session$id
)
if(is.list(extraFields)) {
  for(name in ls(extraFields)) {
    params[[name]] = extraFields[[name]]
  }
}

concerto.table.query(paste0("
UPDATE {{table}} SET 
{{finishedColumn}}='1',
{{updateTimeColumn}}=CURRENT_TIMESTAMP
", getExtraFieldsSql(), "
WHERE id='{{id}}'"), params=params)

sql = NULL
  otherColumnsSql = ""
  if(concerto$dbConnectionParams$driver == "oci8") {
    allColumns = concerto.table.query("SELECT column_name \"col\" FROM user_tab_columns WHERE table_name = UPPER('{{table}}')", list(table=sessionBank$table))[,"col"]
    explicitColumns = toupper(c(
      "id",
      sessionBank$columns$updateTime,
      sessionBank$columns$finished
    ))
    otherColumnsSql = paste(setdiff(allColumns, explicitColumns), collapse=",")
    if(otherColumnsSql != "") { otherColumnsSql = paste0(",", otherColumnsSql) }
    sql = "
        SELECT
        id AS \"id\",
        {{updateTimeColumn}} AS \"updateTime\",
        {{finishedColumn}} AS \"finished\"
        FROM {{table}}
        WHERE id='{{id}}'
    "
} else {
    sql = "SELECT * FROM {{table}} WHERE id='{{id}}'"
}
session = as.list(concerto.table.query(sql, params=list(
  table=sessionBank$table,
  id=session$id,
  updateTimeColumn=sessionBank$columns$updateTime,
  finishedColumn=sessionBank$columns$finished
)))