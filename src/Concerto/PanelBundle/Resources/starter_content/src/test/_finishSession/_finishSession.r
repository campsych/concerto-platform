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

session$finished = 1
session$updateTime = format(Sys.time(), "%Y-%m-%d %X")
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
{{finishedColumn}}=1,
{{updateTimeColumn}}=CURRENT_TIMESTAMP
", getExtraFieldsSql(), "
WHERE id={{id}}"), params=params)