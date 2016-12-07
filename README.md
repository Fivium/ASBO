# ASBO - Active Session Breakdown for Oracle

Displays per second breakdown of active database sessions

- It is a basic session snaps viewer, there are no user accounts etc
- It is a couple of php scripts that run some SQL using php with an OCI8 connection to the database

Demo, running on a overloaded single core server the 2 databases

http://82.165.37.136/oav/db_monitor.php?db=12c

## Use

For **ORACLE STANDARD EDITION** (not needed for enterprise edition), need to setup some snapping, Oracle Snapper for Standard Edition (OSSE)

1. Create the oracle user to hold the snapped data
```sql
SQL> create user dbamgr identified by dbamgr;
```
2. Change directory to osse
3. AS SYS
```sql
SQL> @run_as_sys
```
4. AS DBAMGR
```sql
SQL> @run_as_dbamgr
```
5. Check for errors, and check `dbamgr.session_snaps` being populated

### Use a docker image webserver with everything installed
```
docker pull tomdale55/asbo
docker run --name asbo -d -p 80:80 tomdale55/asbo
```
connect to the container
```
docker exec -it asbo bash
```
the edit, using vim the db_lookup.php
```
vim conf/db_lookup.php
```
### Instruction bellow to do it yourself

### On a linux webserver

1. create folder `asbo` in apache document root e.g. `mkdir /var/www/html/asbo`
2. place all 'web' files in there
3. edit `/var/www/html/asbo/iconfig/db_lookup.php`
  1. enter database details and the user created above
4. access using browser
  `<webserver_addr>/asbo/db_monitor.php?db=<db_name_from_config?`

## Requirements web server

* Apache webserver
* PHP
* OCI8

### Quick install PHP oci8 steps

1. Get and install Instant Client RPMs (on yum based distros)
  * download oracle-instantclient11.2-devel-11.2.0.2.0.x86_64.rpm
  * download oracle-instantclient11.2-basic-11.2.0.2.0.x86_64.rpm
  * `yum install oracle-instantclient*`
2. Install `pear` and `php-devel` if not already installed
  * `yum install php-pear`
  * `yum install php-devel`
3. Configure php with OCI8
  * download oci8.tar.gz
  * `pecl install oci8.tar.gz`
  * The PECL install will prompt for instant client lib path, use `instantclient,<path>` e.g. `instantclient,/path/to/instant/client/lib`
4. SE Linux allow for the oci8 lib (if using SE Linux)
  * Check permisions
    * `ll -Z /usr/lib64/php/modules/`
  * Change if needed
    * `chcon --reference /usr/lib64/php/modules/curl.so /usr/lib64/php/modules/oci8.so`
  * Set some selinux stuff
    * `setsebool -P httpd_can_network_connect on`
    * `setsebool -P httpd_execmem on`
5. Add oci8 to php
  * `vi /etc/php.d/oci.ini`
  * Add `extension=oci8.so`
6. restart apache

To test if the OCI extension for PHP installed suvvessfully, check php_info().

