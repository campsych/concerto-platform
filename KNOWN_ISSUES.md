### Session forking and tempdir()
When using **session_forking=true**, R **tempdir()** can return R directory that no longer exist. If it still exist, it will be shared with other forked sessions.