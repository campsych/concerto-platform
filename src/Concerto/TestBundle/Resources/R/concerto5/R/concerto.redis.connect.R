concerto.redis.connect = function(host, port, password){
    concerto.log("connecting with redis...")

    redisPass = NULL
    if(password != "") { redisPass = password }

    hiredis(
        host = host,
        port = port,
        password = redisPass
    )
}