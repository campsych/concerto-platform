selectedLanguage = language
if(is.na(language)) { selectedLanguage = defaultLanguage }

concerto.log(selectedLanguage, "selected language")

dictionaryTable = fromJSON(dictionaryTable)
if(type == "multiTable") {
  suffixStartIndex = regexpr("_[^_]*$", dictionaryTable$table)
  if(suffixStartIndex==-1) {
    stop(paste0("dictionary mapping table ", dictionaryTable$table, " doesn't contain proper language suffix"))
  }
  
  dictionaryTable$table = paste0(substr(dictionaryTable$table, 1, suffixStartIndex), selectedLanguage)
}

entries = concerto.table.query("SELECT {{keyColumn}} AS entryKey, IF({{langColumn}} IS NULL, {{defaultLanguage}}, {{langColumn}}) AS trans FROM {{table}}", params=list(
  keyColumn = dictionaryTable$columns$entryKey,
  langColumn = selectedLanguage,
  defaultLanguage = defaultLanguage,
  table = dictionaryTable$table
))

c.set("dictionary", entries, global=T)
