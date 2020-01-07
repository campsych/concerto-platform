getWhereClause = function(whereClause, queryType) {
  if((is.na(whereClause) || whereClause == "") && queryType != "update") {
    .elems = NULL
    for(.name in .dynamicInputs) {
      if(is.null(get(.name))) { next }
      .elem = concerto.table.insertParams("{{name}}='{{value}}'", list(
        name=.name,
        value=get(.name)
      ))
      .elems = c(.elems, .elem)
    }
    if(length(.elems) > 0) {
      .elemsJoined = paste(.elems, collapse=" AND ")
      return(paste0("WHERE ", .elemsJoined))
    }
  } else if(length(whereClause) == 1) {
    .id = suppressWarnings(as.numeric(whereClause))
    if(is.character(whereClause) && is.na(.id)) {
      .params = list()
      for(.name in .dynamicInputs) {
        .params[[.name]] = get(.name)
      }
      .elemsJoined = concerto.table.insertParams(whereClause, .params)
      if(.elemsJoined == "") { return(NULL) }
      return(paste0("WHERE ", .elemsJoined))
    } else if(is.numeric(.id)) {
      return(paste0("WHERE id=", whereClause))
    }
  }
  return(NULL)
}
    
getSetClause = function(setClause, queryType) {
  if(is.na(setClause) || setClause == "") {
    if(queryType == "insert") {
      .cols = NULL
      .vals = NULL
      for(.name in .dynamicInputs) {
        if(is.null(get(.name))) { next }
        .cols = c(.cols, .name)
        .val = concerto.table.insertParams("'{{value}}'", list(
          value=get(.name)
        ))
        .vals = c(.vals, .val)
      }
      if(length(.cols) > 0) {
        .colsJoined = paste(.cols, collapse=", ")
        .valsJoined = paste(.vals, collapse=", ")
        return(paste0("(", .colsJoined, ") VALUES (", .valsJoined, ")"))
      }
    } else {
      .elems = NULL
      for(.name in .dynamicInputs) {
        if(is.null(get(.name))) { next }
        .elem = concerto.table.insertParams("{{name}}='{{value}}'", list(
          name=.name,
          value=get(.name)
        ))
        .elems = c(.elems, .elem)
      }
      if(length(.elems) > 0) {
        .elemsJoined = paste(.elems, collapse=", ")
        return(paste0("SET ", .elemsJoined))
      }
    }
  } else if(length(setClause) == 1 && is.character(setClause)) {
    .params = list()
    for(.name in .dynamicInputs) {
      .params[[.name]] = get(.name)
    }
    .elemsJoined = concerto.table.insertParams(setClause, .params)
    return(paste0("SET ", .elemsJoined))
  }
  return(NULL)
}
  
getCustomQuery = function(queryString) {
  .params = list()
  for(.name in .dynamicInputs) {
    .params[[.name]] = get(.name)
  }
  queryString = concerto.table.insertParams(queryString, .params)
  return(queryString)
}

result = NULL
if(queryType == "select") {
  queryString = "SELECT * FROM {{table}}"
  .whereString = getWhereClause(whereClause, queryType)
  if(!is.null(.whereString)) {
    queryString = paste0(queryString, " ", .whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else if(queryType == "insert") {
  queryString = "INSERT INTO {{table}}"
  .setString = getSetClause(setClause, queryType)
  if(!is.null(.setString)) {
    queryString = paste0(queryString, " ", .setString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
  insertId = concerto.table.lastInsertId()
} else if(queryType == "update") {
  queryString = "UPDATE {{table}}"
  .setString = getSetClause(setClause, queryType)
  if(!is.null(.setString)) {
    queryString = paste0(queryString, " ", .setString)
  } else {
    stop("No 'set' clause for UPDATE query")
  }
  .whereString = getWhereClause(whereClause, queryType)
  if(!is.null(.whereString)) {
    queryString = paste0(queryString, " ", .whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else if(queryType == "delete") {
  queryString = "DELETE FROM {{table}}"
  .whereString = getWhereClause(whereClause, queryType)
  if(!is.null(.whereString)) {
    queryString = paste0(queryString, " ", .whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else {
  queryString = getCustomQuery(queryString)
  result = concerto.table.query(queryString)
}
