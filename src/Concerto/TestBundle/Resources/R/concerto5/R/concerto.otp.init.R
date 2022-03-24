concerto.otp.init = function(username, console = "/app/concerto/bin/console"){
  concerto.log("OTP init...")

  output = system(paste0("php ",console," otp:init ", username), intern=T)
  fromJSON(output)
}