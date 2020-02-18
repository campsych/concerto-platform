library(httr)

method = toupper(method)
if(is.null(requestHeaders) || !is.list(requestHeaders)) {
  requestHeaders = c()
} else {
  requestHeaders = unlist(requestHeaders)
}
config = add_headers(.headers=requestHeaders)

concerto.log(paste0(method, " ", url))
response = tryCatch({
  response = switch(
    method,
    POST = POST(url, config, body=requestBody),
    GET = GET(url, config),
    DELETE = DELETE(url, config, body=requestBody),
    UPDATE = UPDATE(url, config, body=requestBody)
  )
  response
}, error = function(e) {
  concerto.log(e, "error")
  return(NULL)
})

.branch = "failure"
if(!is.null(response)) {
  .branch = "success"
  responseStatusCode = response$status_code
  responseBody = content(response)
  responseHeaders = headers(response)
}