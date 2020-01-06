session$finished = 1
session$updateTime = format(Sys.time(), "%Y-%m-%d %X")

sessionBank = fromJSON(sessionBankTable)
params=list(
  table=sessionBank$table,
  finishedColumn=sessionBank$columns$finished,
  updateTimeColumn=sessionBank$columns$updateTime,
  id=session$id
)

concerto.table.query("
UPDATE {{table}} SET 
{{finishedColumn}}=1,
{{updateTimeColumn}}=CURRENT_TIMESTAMP
WHERE id={{id}}", params=params)