<?php
	$query = "SELECT *
	FROM host
	WHERE id = 1;";
	$result = mysqli_query($con, $query);
	$host = mysqli_fetch_assoc($result);
?>