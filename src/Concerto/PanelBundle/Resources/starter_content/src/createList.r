result = list()
if(is.list(sourceList)) {
  result = sourceList
}
for(.name in .dynamicInputs) {
    result[.name] = list(get(.name))
}
