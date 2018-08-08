concerto.var.getDynamicInputs = c.getDynamicInputs = function(){
    result = list()
    flowIndex = length(concerto$flow)
    dynamicInputs = concerto$flow[[flowIndex]]$globals$.dynamicInputs
    if(length(dynamicInputs) > 0) {
        names = ls(dynamicInputs)
        if(length(names) > 0) {
            for(i in 1:length(names)) {
                result[[name]] = concerto$flow[[flowIndex]]$globals[[name]]
            }
        }
    }
    return(result)
}