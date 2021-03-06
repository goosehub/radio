<?php
session_start();
include '../connect.php';

// Used for hosts inserting uploads

if($_SERVER['REQUEST_METHOD'] == 'POST')
{

// Set slug
	$slug = $_POST['slug'];

// Redirect after submit
	header("Location: ../".$slug."/host");

//Set and sanitize known variables for query
	// variable names are long to prevent confusion
	$special = 'host';
	$name = $_SESSION['name'];
	$name = mysqli_real_escape_string($con, $name);
	$time = time();
	date_default_timezone_set('America/New_York');
	$hostCurrentShowNameInput = $_POST['hostCurrentShowNameInput'];
	$hostCurrentShowNameInput = mysqli_real_escape_string($con, $hostCurrentShowNameInput);
	$hostCurrentShowDescInput = $_POST['hostCurrentShowDescInput'];
	$hostCurrentShowDescInput = mysqli_real_escape_string($con, $hostCurrentShowDescInput);
	$hostLengthInput = $_POST['hostLengthInput'];
	$hostLengthInput = mysqli_real_escape_string($con, $hostLengthInput);
	$hostLengthInput = $hostLengthInput * 60;
	$hostQueueLimitInput = $_POST['hostQueueLimitInput'];
	$hostQueueLimitInput = mysqli_real_escape_string($con, $hostQueueLimitInput);
	$hostQueueLimitInput = $hostQueueLimitInput * 60;
	$hostStart = $_POST['hostStart'];
	$hostStart = mysqli_real_escape_string($con, $hostStart);
	$hostYoutubeInput = $_POST['hostYoutubeInput'];
	$hostYoutubeInput = mysqli_real_escape_string($con, $hostYoutubeInput);
	$hostTwitch = $_POST['hostTwitch'];
	$hostTwitch = mysqli_real_escape_string($con, $hostTwitch);
	$hostStream = $_POST['hostStream'];
	$hostStream = mysqli_real_escape_string($con, $hostStream);
	$hostAudioStream = $_POST['hostAudioStream'];
	$hostAudioStream = mysqli_real_escape_string($con, $hostAudioStream);
	$hostAudioStreamOn = $_POST['hostAudioStreamOn'];
	$hostAudioStreamOn = mysqli_real_escape_string($con, $hostAudioStreamOn);
	// files must be sanitized later

// Get Valid Passwords
	include '../ajax/host-password.php';
	$hostPassword = $hostPassword['password'];
	include '../ajax/master-password.php';
	$masterPassword = $masterPassword['password'];

// Translate hostStart into UNIX
	$hostStart = strtotime($hostStart);

// Allowed tags for host navbar
	$whiteTags = '<iframe><a><abbr><acronym><address><area><b><bdo><big><blockquote><br><button><caption><center><cite><code><col><colgroup><dd><del><dfn><dir><div><dl><dt><em><fieldset><font><form><h1><h2><h3><h4><h5><h6><hr><i><img><input><ins><kbd><label><legend><li><map><menu><ol><optgroup><option><p><pre><q><s><samp><select><small><span><strike><strong><sub><sup><table><tbody><td><textarea><tfoot><th><thead><u><tr><tt><u><ul><var>';

// If current video will be clear, set as ID to be deleted
	if (isset($_POST['hostClearCurrent'])) {
		include '../ajax/find-current.php';
		$hostDeleteItem = $current['id'];
	}
// Else, prepare any other ID's to be deleted
	else
	{
		$hostDeleteItem = $_POST['hostDeleteItem'];
		$hostDeleteItem = mysqli_real_escape_string($con, $hostDeleteItem);
	}

	$passwordInput = $_POST['passwordInput'];
	$passwordInput = mysqli_real_escape_string($con, $passwordInput);

// Start entering data

// Passwords in process of being transfered to sessions. Foo used in place
$foo = TRUE;
	if ( $foo
// MASTER KEY
		// $passwordInput === $masterPassword
		// ||
// Temporary for event hosts
		// $passwordInput === $hostPassword
		)
	{
// Current show Name
		if ($hostCurrentShowNameInput)
		{
		      $query = "UPDATE rooms 
		      SET showName = '". $hostCurrentShowNameInput ."'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query);  
		}
// Current Show Description
		if ($hostCurrentShowDescInput)
		{
			$hostCurrentShowDescInput = strip_tags($hostCurrentShowDescInput, $whiteTags);
		      $query = "UPDATE rooms 
		      SET showDescription = '". $hostCurrentShowDescInput ."'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query);  
		}
// Max Length
		if ($hostLengthInput)
		{
		      $query = "UPDATE rooms 
		      SET length = '". $hostLengthInput ."'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query);  
		}
// Max Queue
		if ($hostQueueLimitInput)
		{
		      $query = "UPDATE rooms 
		      SET queue = '". $hostQueueLimitInput ."'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query);  
		}
// Set Shuffle
		if (isset($_POST['hostShuffle']))
		{
		      $query = "UPDATE rooms 
		      SET shuffle = '1'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query); 
		} else {
		      $query = "UPDATE rooms 
		      SET shuffle = '0'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query); 
		  }
// Clear Past Uploads
		if (isset($_POST['hostClearPastUploads']))
		{
		      $query = "DELETE FROM upload 
		      WHERE end < '". $time ."';";
		      $result = mysqli_query($con, $query);  
		}
// Clear Queue
		if (isset($_POST['hostClearQueueInput']))
		{
		      $query = "DELETE FROM upload 
		      WHERE start > '". $time ."'
		      AND slug = '".$slug."'
		      AND special != 'timed';";
		      $result = mysqli_query($con, $query);  
		}
// If current or future is being cleared
		if (isset($hostDeleteItem)
			|| isset($_POST['hostClearCurrent']))
		{
// Check if current is deleted
			if (isset($_POST['hostClearCurrent']))
			{
// change number to whatever the current hostInfo refresh time is
// Keep consistant
				$reloadTime = $time + 20;
// Set reload command to be found by client
			    $query = "UPDATE rooms 
			    SET reload = '". $reloadTime ."'
			    WHERE slug = '".$slug."';";
			    $result = mysqli_query($con, $query); 
// Set ID to be deleted to be the current
				include '../ajax/find-current.php';
				$hostDeleteItem = $current['id'];
			}
// find duration of deleted item
			$query = "SELECT * FROM upload 
			WHERE id = '". $hostDeleteItem ."'
		    AND slug = '".$slug."';";
			$deleteItemResult = mysqli_query($con, $query); 
			$deletedItem = mysqli_fetch_assoc($deleteItemResult);
// Deleted selected
		    $query = "DELETE FROM upload 
		    WHERE id = '". $hostDeleteItem ."'
		    AND slug = '".$slug."';";
		    $deletedResult = mysqli_query($con, $query); 
// Move the rest forward that are not timed
  		    $query = "SELECT * FROM upload 
		    WHERE special != 'timed'
		    AND start >= '".$deletedItem['start']."'
		    AND slug = '".$slug."';";
			if ($advanceResult = mysqli_query($con, $query))
			{
		        while($advance = mysqli_fetch_assoc($advanceResult)) 
		        {
// Set new time slot
		        	$newStart = $advance['start'] - $deletedItem['duration'];
		        	$newEnd = $advance['end'] - $deletedItem['duration'];
		        	$newScheduled = ''.$advance['scheduled'].' - '.$deletedItem['duration'].' secs';
		        	$query = "UPDATE upload 
						      SET start = '". $newStart ."',
						      end = '". $newEnd ."',
						      scheduled = '". $newScheduled ."'
						      WHERE id = '".$advance['id'] ."'
						      AND special != 'timed'
							  AND slug = '".$slug."';";
		        	$result = mysqli_query($con, $query); 
		        }
		    }
		}
// Twitch stream
		if ($hostTwitch)
		{			
// Reload if stream has changed
			$query = "SELECT twitch from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($twitch_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($twitch_status['twitch'] === $hostTwitch)
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
			$query = "UPDATE rooms 
			SET twitch = '". $hostTwitch ."'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  
		}
// Twitch on
		if ($hostStream)
		{
// Reload if stream was off
			$query = "SELECT twitch_on from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($twitch_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($twitch_status['twitch_on'] === '0')
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
// Set reload
			$query = "UPDATE rooms 
			SET twitch_on = '1'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  
		}
// Twitch off
		else
		{
// Reload if stream was on
			$query = "SELECT twitch_on from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($twitch_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($twitch_status['twitch_on'] === '1')
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
			$query = "UPDATE rooms 
			SET twitch_on = '0'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  			
		}
// Audio stream
		if ($hostAudioStream)
		{			
// Reload if stream has changed
			$query = "SELECT audio_stream from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($audio_stream_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($audio_stream_status['audio_stream'] === $hostAudioStream)
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
			$query = "UPDATE rooms 
			SET audio_stream = '". $hostAudioStream ."'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  
		}
// Audio Stream on
		if ($hostAudioStreamOn)
		{
// Reload if stream was off
			$query = "SELECT audio_stream_on from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($audio_stream_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($audio_stream_status['audio_stream_on'] === '0')
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
// Set reload
			$query = "UPDATE rooms 
			SET audio_stream_on = '1'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  
		}
// Audio Stream off
		else
		{
// Reload if stream was on
			$query = "SELECT audio_stream_on from rooms 
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query); 
			while($audio_stream_status = mysqli_fetch_assoc($result)) 
			{ 
				if ($audio_stream_status['audio_stream_on'] === '1')
				{
					$reloadTime = $time + 20;
					$query = "UPDATE rooms 
					SET reload = '". $reloadTime ."'
					WHERE slug = '".$slug."';";
					$result = mysqli_query($con, $query); 
				}
			}
			$query = "UPDATE rooms 
			SET audio_stream_on = '0'
			WHERE slug = '".$slug."';";
			$result = mysqli_query($con, $query);  			
		}
// Upload new background image
		if ($_FILES["hostBackgroundInput"]["name"])
		{
// File info
			$image_info = getimagesize($_FILES["hostBackgroundInput"]["tmp_name"]);
			$image_width = $image_info[0];
			$image_height = $image_info[1];
			$allowedExts = array("gif", "jpeg", "jpg", "png");
			$temp = explode(".", $_FILES["hostBackgroundInput"]["name"]);
			$extension = end($temp);
//Validate
			if (
			(
			   ($_FILES["hostBackgroundInput"]["type"] == "image/gif")
			|| ($_FILES["hostBackgroundInput"]["type"] == "image/jpeg")
			|| ($_FILES["hostBackgroundInput"]["type"] == "image/jpg")
			|| ($_FILES["hostBackgroundInput"]["type"] == "image/pjpeg")
			|| ($_FILES["hostBackgroundInput"]["type"] == "image/x-png")
			|| ($_FILES["hostBackgroundInput"]["type"] == "image/png")
			)
			&& ($_FILES["hostBackgroundInput"]["size"] < 320000000)
			// && ($image_height < 100000)
			// && ($image_width < 100000)
			// && ($image_height > 100)
			// && ($image_width > 100)
			&& in_array($extension, $allowedExts)
			) 
			{	
// Error check
			  if ($_FILES["hostBackgroundInput"]["error"] > 0) {
			    $data['errorCode'] = "Return Code: " . $_FILES["hostBackgroundInput"]["error"] . "<br>";
			  } 
			  else
			  {
// Rename file to UNIX time
          $filename = time().'.'.$extension;

// Move Files
		      move_uploaded_file($_FILES["hostBackgroundInput"]["tmp_name"],
		      "../upload/background/" . $filename);
// Prepare for ajax
		      $hostBackgroundInput = $filename;
// Query
		      $query = "UPDATE rooms 
		      SET background = '". $hostBackgroundInput ."'
		      WHERE slug = '".$slug."';";
		      $result = mysqli_query($con, $query);  
		}
	}
}

// 
// Upload
// 

	if (strlen($hostYoutubeInput) > 10)
		{

//youtube logic below

// Get youtube ID from URL
		parse_str( parse_url( $hostYoutubeInput, PHP_URL_QUERY ), $my_array_of_vars );
		$youtubeID = $my_array_of_vars['v'];

//check if valid
//if valid, ignore audio and insert youtube into DB
			if (strlen($youtubeID) === 11)
				{
//get youtube video duration and title
				$url = "http://gdata.youtube.com/feeds/api/videos/". $youtubeID;
				$doc = new DOMDocument;
				$doc->load($url);
// title is not retrieved for all videos 
				// $title = $doc->getElementsByTagName("title")->item(0)->nodeValue;
				$duration = $doc->getElementsByTagName('duration')->item(0)->getAttribute('seconds');
// Add time for loading time
				$duration = $duration + 5;
// Find next available slot
				include '../ajax/host-insert.php';
// Query
			      $query = "INSERT INTO upload 
			      			(name, time, youtube, duration,
			      			start, end, scheduled, special, slug)
			      			VALUES('". $name ."', '". $time ."',
			       			'". $youtubeID ."', '". $duration ."', '". $start ."',
			       			'". $end ."', '". $scheduled ."', '".$special."', '".$slug."');";
			      $result = mysqli_query($con, $query);  

//remove uneeded files if exists
				$hostImageInput = '';
				$hostAudioInput = '';
				}
			}
			if ($_FILES["hostAudioInput"]["size"] > 1)
			{


				//rest is for file uploads only


// GetID3 function
			function get_duration($audioPath, $audioFile) 
			{ 
			// include getID3() library 
			require_once('../resources/tools/getID3-1.9.8/getid3/getid3.php'); 
			$getID3 = new getID3();
			//set up path
			$FullFileName = realpath($audioPath.'/'.$audioFile); 
			if (is_file($FullFileName)) { 
			//limit time to work function
				set_time_limit(120); 
			//analyze
				$ThisFileInfo = $getID3->analyze($FullFileName); 
				getid3_lib::CopyTagsToComments($ThisFileInfo); 
			//return seconds
				$duration = $ThisFileInfo['playtime_seconds']; 
				return $duration; 
				} 
			} 

// File info
				$image_info = getimagesize($_FILES["hostImageInput"]["tmp_name"]);
				$audio_info = filesize($_FILES["hostAudioInput"]["tmp_name"]);
				$image_width = $image_info[0];
				$image_height = $image_info[1];
				$allowedExts = array("gif", "jpeg", "jpg", "png", "mp3", "ogg", "flak", "wav");
				$temp = explode(".", $_FILES["hostImageInput"]["name"]);
				$extension = end($temp);

//Validate
				if (
				(
				   ($_FILES["hostImageInput"]["type"] == "image/gif")
				|| ($_FILES["hostImageInput"]["type"] == "image/jpeg")
				|| ($_FILES["hostImageInput"]["type"] == "image/jpg")
				|| ($_FILES["hostImageInput"]["type"] == "image/pjpeg")
				|| ($_FILES["hostImageInput"]["type"] == "image/x-png")
				|| ($_FILES["hostImageInput"]["type"] == "image/png")
				)
				&& ($_FILES["hostImageInput"]["size"] < 8000000)
				&& ($_FILES["hostAudioInput"]["size"] < 64000000000)
				&& ($image_height < 3200)
				&& ($image_width < 3200)
				&& ($image_height > 200)
				&& ($image_width > 200)
				&& in_array($extension, $allowedExts)
				) 
				{	
// Error check
				  if ($_FILES["hostImageInput"]["error"] > 0) {
				    $data['errorCode'] = "Return Code: " . $_FILES["hostImageInput"]["error"] . "<br>";
				  } 
				  else if ($_FILES["hostAudioInput"]["error"] > 0) {
				    $data['errorCode'] = "Return Code: " . $_FILES["hostAudioInput"]["error"] . "<br>";
				  } 
				  else
				  {
// Rename file to UNIX time
	          $filename = time().'.'.$extension;

// Move Files
				      move_uploaded_file($_FILES["hostImageInput"]["tmp_name"],
				      "../upload/images/" . $filename);
				      move_uploaded_file($_FILES["hostAudioInput"]["tmp_name"],
				      "../upload/audio/" . $_FILES["hostAudioInput"]["name"]);

//Get audio duration
				      $duration = get_duration("../upload/audio", $_FILES["hostAudioInput"]["name"]);
				      $duration = floor($duration);
// Add time for ads and loading time
// Will need monitoring for adjusting
				      $duration = $duration + 5;

// Find next available slot
					  include '../ajax/host-insert.php';

// Prepare for ajax
				      $hostImageInput = $filename;
		  		      $hostAudioInput = $_FILES["hostAudioInput"]["name"];
					  $hostAudioInput = mysqli_real_escape_string($con, $hostAudioInput);

// Query
				      $query = "INSERT INTO upload 
				      (name, time, audio, image, duration, start, end, scheduled, special, slug)
				      VALUES('". $name ."', '". $time ."', '". $hostAudioInput ."',
				       '". $hostImageInput ."', '". $duration ."'
				       , '". $start ."', '". $end ."', '". $scheduled ."', '".$special."', '".$slug."');";
				      $result = mysqli_query($con, $query);   
				  }
		    }
		}
	}
}

?>