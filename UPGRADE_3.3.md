MySQL change

Update from mysql_native_password to caching_sha2_password.


One can use the following to migrate a user to the new auth plugin:

ALTER USER 'user'@'host' IDENTIFIED WITH caching_sha2_password;
ALTER USER 'user'@'host' IDENTIFIED BY 'password';


