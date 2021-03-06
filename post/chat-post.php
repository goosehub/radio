<?php
session_start();

// Used to insert chat message

$time = time();

include '../connect.php';

// If new message
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
// Set and secure data
	$slug = $_POST['slug'];
	$name = $_SESSION[$slug]['name'];
	$message = $_POST['message'];
	$message = mysqli_real_escape_string($con, $message);
	date_default_timezone_set('America/New_York');
	$timestamp = time();

// Find who wrote the most recent message
	$query = "SELECT name
	        from chat
	        WHERE slug = '".$slug."'
	        AND CHAR_LENGTH(name) > 0
	        ORDER BY id 
	        DESC LIMIT 1;";
	$result = mysqli_query($con, $query);
	$recentName = mysqli_fetch_assoc($result);

// Check length of message
	if (strlen($message) < 512
		&& $message != '')
	{
			$query = "INSERT INTO
	        chat(name, message, slug, timestamp)
	        VALUES('$name', '$message', '$slug', '$timestamp');"; 
			$result = mysqli_query($con, $query);

	}

}  
?>