getWhereClause = function(where, type) {
  if((is.na(where) || where == "") && type != "update") {
    elems = NULL
    for(name in .dynamicInputs) {
      elem = concerto.table.insertParams("{{name}}='{{value}}'", list(
        name=name,
        value=get(name)
      ))
      elems = c(elems, elem)
    }
    if(length(elems) > 0) {
      elemsJoined = paste(elems, collapse=" AND ")
      return(paste0("WHERE ", elemsJoined))
    }
  } else if(length(where) == 1) {
    id = suppressWarnings(as.numeric(where))
    if(is.character(where) && is.na(id)) {
      params = list()
      for(name in .dynamicInputs) {
        params[[name]] = get(name)
      }
      elemsJoined = concerto.table.insertParams(where, params)
      return(paste0("WHERE ", elemsJoined))
    } else if(is.numeric(id)) {
      return(paste0("WHERE id=",where))
    }
  }
  return(NULL)
}
    
getSetClause = function(set, type) {
  if(is.na(set) || set == "") {
    if(type == "insert") {
      cols = NULL
      vals = NULL
      for(name in .dynamicInputs) {
        cols = c(cols, name)
        val = concerto.table.insertParams("'{{value}}'", list(
          value=get(name)
        ))
        vals = c(vals, val)
      }
      if(length(cols) > 0) {
        colsJoined = paste(cols, collapse=", ")
        valsJoined = paste(vals, collapse=", ")
        return(paste0("(",colsJoined,") VALUES (",valsJoined,")"))
      }
    } else {
      elems = NULL
      for(name in .dynamicInputs) {
        elem = concerto.table.insertParams("{{name}}='{{value}}'", list(
          name=name,
          value=get(name)
        ))
        elems = c(elems, elem)
      }
      if(length(elems) > 0) {
        elemsJoined = paste(elems, collapse=", ")
        return(paste0("SET ", elemsJoined))
      }
    }
  } else if(length(set) == 1 && is.character(set)) {
    params = list()
    for(name in .dynamicInputs) {
      params[[name]] = get(name)
    }
    elemsJoined = concerto.table.insertParams(set, params)
    return(paste0("SET ", elemsJoined))
  }
  return(NULL)
}
  
getCustomQuery = function(queryString) {
  params = list()
  for(name in .dynamicInputs) {
    params[[name]] = get(name)
  }
  queryString = concerto.table.insertParams(queryString, params)
  return(queryString)
}

result = NULL
if(queryType == "select") {
  queryString = "SELECT * FROM {{table}}"
  whereString = getWhereClause(whereClause, queryType)
  if(!is.null(whereString)) {
    queryString = paste0(queryString, " ", whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else if(queryType == "insert") {
  queryString = "INSERT INTO {{table}}"
  setString = getSetClause(setClause, queryType)
  if(!is.null(setString)) {
    queryString = paste0(queryString, " ", setString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else if(queryType == "update") {
  queryString = "UPDATE {{table}}"
  setString = getSetClause(setClause, queryType)
  if(!is.null(setString)) {
    queryString = paste0(queryString, " ", setString)
  } else {
    stop("No 'set' clause for UPDATE query")
  }
  whereString = getWhereClause(whereClause, queryType)
  if(!is.null(whereString)) {
    queryString = paste0(queryString, " ", whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else if(queryType == "delete") {
  queryString = "DELETE FROM {{table}}"
  whereString = getWhereClause(whereClause, queryType)
  if(!is.null(whereString)) {
    queryString = paste0(queryString, " ", whereString)
  }
  result = concerto.table.query(queryString, params=list(
    table=table
  ))
} else {
  queryString = getCustomQuery(queryString)
  result = concerto.table.query(queryString)
}
