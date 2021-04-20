if(is.na(columnPrefix)) { columnPrefix = "" }
selectedLanguage = language
if(is.na(language)) { selectedLanguage = defaultLanguage }

concerto.log(selectedLanguage, "selected language")

dictionaryTable = fromJSON(dictionaryTable)
if(type == "multiTable") {
  table = dictionaryTable$table
  suffixStartIndex = regexpr("_[^_]*$", table)
  if(suffixStartIndex==-1) {
    stop(paste0("dictionary mapping table ", table, " doesn't contain proper language suffix"))
  }

  dictionaryTable$table = paste0(substr(table, 1, suffixStartIndex), selectedLanguage)
  dictionaryTable$defaultTable = paste0(substr(table, 1, suffixStartIndex), defaultLanguage)
}

languageColumn = paste0(columnPrefix, selectedLanguage)
defaultLanguageColumn = paste0(columnPrefix, defaultLanguage)

params=list(
  keyColumn = dictionaryTable$columns$entryKey,
  languageColumn = languageColumn,
  defaultLanguageColumn = defaultLanguageColumn,
  table = dictionaryTable$table,
  defaultTable = dictionaryTable$defaultTable
)

entries = NULL
if(type == "multiTable") {
  entries = concerto.table.query("
SELECT 
t1.{{keyColumn}} AS entryKey, 
IF(t1.{{languageColumn}} IS NULL, t2.{{defaultLanguageColumn}}, t1.{{languageColumn}}) AS trans 
FROM {{table}} AS t1
LEFT JOIN {{defaultTable}} AS t2 ON t2.{{keyColumn}}=t1.{{keyColumn}}", params=params)
} else {
  entries = concerto.table.query("
SELECT 
{{keyColumn}} AS entryKey, 
IF({{languageColumn}} IS NULL, {{defaultLanguageColumn}}, {{languageColumn}}) AS trans 
FROM {{table}}", params=params)
}

c.set("dictionary", entries, global=T)