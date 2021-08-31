# JSON2MYSQL
JSON2MYSQL is a simple PHP script that creates Insert statments that inserts JSON data into a pre-made MySQL table with the help of the MySQLi plugin.

JSON2MYSQL does NOT create the `create table`. The functionality might get implemented but it is unlikely to be my pirorty at the memoment.

The script is designed for working alongside MSSQL2JSON. While it would work for any other JSON file with the correct format, there is no reason to do so. 

## Feature
* create insert statement from JSON file created by MSSQL2JSON then inseert the data into a stuctrually compatible table.

## Requirement
* PHP with MySQLi plug-in installed
* JSON file created from MSSQL2JSON
* Empty Table with columes correctly setup for import 

## Instructions
1. Download or git clone the project if you feel like being fancy
1. Copy to your website directory if you plan to run in a browser. However running in a browser will not shows the run status of the script while running 
1. Change the required parameters as stated in the script
  * Database IP address / Hostname 
  * Database Table Name
  * User Name
  * Password
1. Run the script in Command Prompt/bash with `php json2mysql.php`  

