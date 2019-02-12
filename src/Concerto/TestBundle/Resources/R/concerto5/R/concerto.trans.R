concerto.trans = c.trans = function(key){
    dictionary = c.get("dictionary", global=T)
    if(is.null(dictionary)) { return(key) }
    dictionaryLength = dim(dictionary)[1]
    if(dictionaryLength > 0) {
        for(i in 1:dictionaryLength) {
            if(dictionary[i,"entryKey"] == key) {
                return(dictionary[i, "trans"])
            }
        }
    }
    return(key)
}