<?php
$file_dir='/var/www/html/share/res'; # i have to find a way to setup that as a constant in the beginning of the script
$pg_db='share';
$pg_user='share';
$pg_pwd='1d58245c3d87a1ad066#3d0ebcb0bcb16';
$pg_host='::1'; 

# to connect to that db : psql -h localhost -d share -U share --password

function get_conn()
{ 
	global $pg_db;
	global $pg_user;
	global $pg_pwd;
	global $pg_host; 
	$conn = new PDO('pgsql:dbname='.$pg_db.' host='.$pg_host.' user='.$pg_user.' password='.$pg_pwd);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $conn;
} 
?>