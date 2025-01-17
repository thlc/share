<?php 

require_once("conf/config.php"); 

function append($conn, $duration, $file, $password)
{ 
	// insert
	$conn->query("INSERT INTO data() VALUES()");

	// calculate values
	$id = $conn->lastInsertId('data_id_seq'); 

	if (isset($duration)) {
		$duration = intval($duration);
	} else {
		$duration = 24*60; // one day
	}
	$end = strtotime("+".$duration." minutes");
	$send = date('Y-m-d H:i:s', $end);

	$filename = FILE_DIR.'/'.$id;
	$mime_type = mime_content_type($file['tmp_name']);

	if (!(is_dir(FILE_DIR))) {
		mkdir(FILE_DIR);
	}
	move_uploaded_file($_FILES['data']['tmp_name'], $filename); 
	chmod(FILE_DIR.'/'.$id,0640);

	if (isset($password)) {
		$hash = password_hash($password, PASSWORD_DEFAULT);
	}
	else { $hash=""; } 

	$query = $conn->prepare('UPDATE data SET duration = :duration, filename = :filename, mime_type = :mime_type, end_valid = :end_valid, hash = :hash WHERE id = :id'); 
	$query->bindValue(":duration", $duration, PDO::PARAM_INT);
	$query->bindValue(":filename", $filename, PDO::PARAM_STR);
	$query->bindValue(":mime_type", $mime_type, PDO::PARAM_STR);
	$query->bindValue(":end_valid", $send, PDO::PARAM_STR);
	$query->bindValue(":hash", $hash, PDO::PARAM_STR);
	$query->bindValue(":id", $id, PDO::PARAM_INT);
	$query->execute(); 
	return $id;
}

function get($conn, $id, $password)
{
	$sql = 'SELECT * FROM data WHERE id = :id';
	$query = $conn->prepare($sql);
	$query->bindValue(":id", $id, PDO::PARAM_INT);
	$query->execute();
	$res = $query->fetch();
	if ($res !== null) {
		$err = null; //TODO replace that with DateTime
		$end = strtotime($res['end_valid']);
		$now = time();
		if ($end < $now) {
			$res = NULL;
		} 

		// if we got a password in the record, then we test, otherwise we don't
		$hash = $res['hash'];
		if ($hash != "") {
			if (!password_verify($password, $hash)) {
				$object = NULL;
				$err = "incorrect password";
			}
		} 
	}; 
	if (!isset($res) && (!isset($err))) {
		$err="not found"; }

	if (isset($err)) {
		return $err."\n"; }
	else { 
		header(sprintf('Content-Type: %s', $res['mime_type']));
		//header(sprintf('Content-Disposition: attachment; filename=%s', $res['filename'])); // uncomment to start a d/l on the client side
		$filename = $res['filename'];
		return file_get_contents($filename);
	}
}



function get_conn()
{ 
	$conn = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $conn;
} 

function clear($conn) {
	$sql = 'SELECT id, filename FROM data WHERE end_valid < NOW()';
	$query = $conn->prepare($sql);
	$query->execute();
	$qdel = $conn->prepare('DELETE FROM data WHERE id = :id');
	while ($res = $query->fetch()) { 
		$id = $res['id'];
		$qdel->bindValue(":id", $id, PDO::PARAM_INT);
		$qdel->execute(); 
		if (file_exists($res['filename'])) {
			unlink($res['filename']); 
		}
	}
}

function init($conn) { 
	$sql = sprintf('CREATE TABLE data(id INTEGER AUTO_INCREMENT, filename VARCHAR(200), mime_type VARCHAR(200), duration INTEGER, end_valid TIMESTAMP, hash VARCHAR(512), PRIMARY KEY(id));');
	$query = $conn->prepare($sql);
	$query->execute(); 
	error_log('share initialized database');
} 

function init_if_needed($conn) {
	global $tablename;
	$sql = "show tables;";
	$query = $conn->prepare($sql);
	$query->execute(array($tablename));
	$count = $query->rowCount();
	if ($count == 0) {
		init($conn);
	} 
}

function page_url() { 
	$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if (($_SERVER["SERVER_PORT"] != "80") and ($_SERVER["SERVER_PORT"] != "443"))
	{
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} 
	else 
	{
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL; 
}


?>
