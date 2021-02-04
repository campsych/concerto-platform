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
    POST = POST(url, config, body=requestBody, encode=requestBodyEncode),
    GET = GET(url, config),
    DELETE = DELETE(url, config, body=requestBody, encode=requestBodyEncode),
    UPDATE = UPDATE(url, config, body=requestBody, encode=requestBodyEncode),
    PATCH = PATCH(url, config, body=requestBody, encode=requestBodyEncode)
  )
  response
}, error = function(e) {
  concerto.log(e, "error")
  return(NULL)
})

.branch = "failure"
responseStatusCode = NULL
responseBody = NULL
responseHeaders = NULL
if(!is.null(response)) {
  concerto.log(response$status_code, "status code")
  concerto.log(content(response), "response content")
  
  responseStatusCode = response$status_code
  responseBody = content(response)
  responseHeaders = headers(response)
  if(responseStatusCode < 400) {
    .branch = "success"
  }
}