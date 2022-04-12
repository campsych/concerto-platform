concerto.otp.authorize = function(username, secret, code, console = "/app/concerto/bin/console") {
  concerto.log("OTP authorize...")

  output = system(paste0("php ", console, " otp:authorize ", username, " ", secret, " ", code), intern = T)
  fromJSON(output)
}