<?php
session_start();
include '../connect.php';

// Used to insert uploads

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
// Empty Errors
	$_SESSION['errLength'] = $_SESSION['errRepeat'] = $_SESSION['errCode'] = 
	$_SESSION['errImgSize'] = $_SESSION['errAudioSize'] = $_SESSION['errFileType'] = 
	$_SESSION['errQueueLimit'] = $_SESSION['errYoutube'] = $_SESSION['errRickRoll'] = '';
//set known variables for query
	if (isset($_SESSION['name']))
	{
		$name = $_SESSION['name'];
	}
	else
	{
		$name = 'anonymous';
	}
	$name = mysqli_real_escape_string($con, $name);
	$time = time();
	$limit = $time + 900;
	date_default_timezone_set('America/New_York');
	$youtubeInput = $_POST['youtubeInput'];
	$slug = $_POST['slug'];
	$youtubeInput = mysqli_real_escape_string($con, $youtubeInput);
	// files must be sanitized later

// Load for later
include '../ajax/host-ajax.php';

// Find queue limit
include '../ajax/queue-limit.php';
// 
	if ($queueLimit['end'] > 1)
	{
// Queue limit error
		$_SESSION['errQueueLimit'] = 'The queue is now full. Better luck next time.';
	}
	else {
// Queue not full

//check if youtube input exists
if (strlen($youtubeInput) > 10)
	{

//youtube logic below

// Get youtube ID from URL
	parse_str( parse_url( $youtubeInput, PHP_URL_QUERY ), $my_array_of_vars );
	$youtubeID = $my_array_of_vars['v'];

// Check database to see if youtubeID already exists
			$query = "SELECT youtube
			FROM upload
			WHERE youtube = '".$youtubeID."'
		    AND slug = '".$slug."';";
			$result = mysqli_query($con, $query);
			$row = mysqli_fetch_assoc($result);

//check if valid and non repeating youtubeID
//if valid, ignore audio and insert youtube into DB
	if ($row['youtube'] === $youtubeID) {
// Report repeat error
		$_SESSION['errRepeat'] = "This has already been played recently";
	}
	if (strlen($youtubeID) === 11
		&& $row['youtube'] !== $youtubeID)
		{
// Function for finding youtube video duration
		function getDurationSeconds($duration){
		    preg_match_all('/[0-9]+[HMS]/',$duration,$matches);
		    $duration=0;
		    foreach($matches as $match){    
		        foreach($match as $portion){        
		            $unite=substr($portion,strlen($portion)-1);
		            switch($unite){
		                case 'H':{  
		                    $duration +=    substr($portion,0,strlen($portion)-1)*60*60;            
		                }break;             
		                case 'M':{                  
		                    $duration +=substr($portion,0,strlen($portion)-1)*60;           
		                }break;             
		                case 'S':{                  
		                    $duration +=    substr($portion,0,strlen($portion)-1);          
		                }break;
		            }
		        }
		    }
		     return $duration;
		}
// Get data from youtube using this API key
		$api_key = "AIzaSyD_lT8RkN6KffGEfJ3xBcBgn2VZga-a05I";
		$url = "https://www.googleapis.com/youtube/v3/videos?id=" . $youtubeID . "&key=" . $api_key . "%20&part=snippet,contentDetails,statistics,status";
		$json = file_get_contents($url); 
		$data = json_decode($json);
		$public = $data->items;
// If not public, give error
		if (! $public)
		{
			$_SESSION['errYoutube'] = 'This is a private video';
		}
		else
		{
			$status = $data->items[0]->status;
			$embeddable = $data->items[0]->status->embeddable;
// If not embeddable, give error
			if (!$embeddable)
			{
				$_SESSION['errYoutube'] = 'This video is not allowed to be embedded';
			}
// Else, set duration
			else
			{
				$duration = $data->items[0]->contentDetails->duration;
				$duration = getDurationSeconds($duration);
// Add time for ads and loading time
				$duration = $duration + 5;

// Check duration
				if ($duration > $host['length'])
				{
// Report length error
					$_SESSION['errLength'] = "This is too long";
				}
				else if ($youtubeID === 'dQw4w9WgXcQ') 
				{
					$_SESSION['errRickRoll'] = 'We\'re no strangers to Rick. You know the rules and so do I.';
				}
				else
				{
// Find next available slot
					$_SESSION['successful_upload'] = 'Your video is coming up.';
					include '../ajax/find-slot.php';
					include '../ajax/find-end.php';

// Query
					$query = "INSERT INTO upload 
					(name, time, youtube, duration, start, end, scheduled, slug)
					VALUES('". $name ."', '". $time ."',
					'". $youtubeID ."', '". $duration ."', '". $start ."',
					'". $end ."', '". $scheduled ."', '".$slug."');";
					$result = mysqli_query($con, $query);  

//remove uneeded files if exists
					$imageInput = '';
					$audioInput = '';
					}
				}
			}
		}
	}
	else
	{


//rest is for file uploads only


	$slug = $_POST['slug'];

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
		set_time_limit(60); 
//analyze
		$ThisFileInfo = $getID3->analyze($FullFileName); 
		getid3_lib::CopyTagsToComments($ThisFileInfo); 
//return seconds
		$duration = $ThisFileInfo['playtime_seconds']; 
		return $duration; 
		} 
	} 

// File info
		$image_info = getimagesize($_FILES["imageInput"]["tmp_name"]);
		$audio_info = filesize($_FILES["audioInput"]["tmp_name"]);
		$image_width = $image_info[0];
		$image_height = $image_info[1];
		$allowedExts = array("gif", "jpeg", "jpg", "png", "mp3", "ogg", "flak", "wav");
		$temp = explode(".", $_FILES["imageInput"]["name"]);
		$extension = end($temp);

//Validate
		if (
		(
		   ($_FILES["imageInput"]["type"] == "image/gif")
		|| ($_FILES["imageInput"]["type"] == "image/jpeg")
		|| ($_FILES["imageInput"]["type"] == "image/jpg")
		|| ($_FILES["imageInput"]["type"] == "image/pjpeg")
		|| ($_FILES["imageInput"]["type"] == "image/x-png")
		|| ($_FILES["imageInput"]["type"] == "image/png")
		)
		&& ($_FILES["imageInput"]["size"] < 4000000)
		&& ($_FILES["audioInput"]["size"] < 64000000)
		// && ($image_height < 3200)
		// && ($image_width < 3200)
		// && ($image_height > 200)
		// && ($image_width > 200)
		&& in_array($extension, $allowedExts)
		) 
		{	
// Error check
		  if ($_FILES["imageInput"]["error"] > 0) {
		    $_SESSION['errCode'] = "Return Code: " . $_FILES["imageInput"]["error"] . "<br/>";
		  } 
		  else if ($_FILES["audioInput"]["error"] > 0) {
		    $_SESSION['errCode'] = "Return Code: " . $_FILES["audioInput"]["error"] . "<br/>";
		  } 
		  else
		  {
// Rename file to UNIX time
	          $filename = time().'.'.$extension;
// Move Files
		      move_uploaded_file($_FILES["imageInput"]["tmp_name"],
		      "../upload/images/" . $filename);
		      move_uploaded_file($_FILES["audioInput"]["tmp_name"],
		      "../upload/audio/" . $_FILES["audioInput"]["name"]);

//Get audio duration
		      $duration = get_duration("../upload/audio", $_FILES["audioInput"]["name"]);
		      $duration = floor($duration);
// Add time for ads and loading time
		      $duration = $duration + 5;
		      		if ($duration > $host['length'])
		      		{
// Report length error
					$_SESSION['errLength'] = "This is too long";
					}
					else
					{
// find next available slot
				include '../ajax/find-slot.php';
				include '../ajax/find-end.php';

// Prepare for ajax
			      $imageInput = $filename;
	  		      $audioInput = $_FILES["audioInput"]["name"];
				  $audioInput = mysqli_real_escape_string($con, $audioInput);

// Query
			      $query = "INSERT INTO upload 
			      (name, time, audio, image, duration, start, end, scheduled, slug)
			      VALUES('". $name ."', '". $time ."', '". $audioInput ."',
			       '". $imageInput ."', '". $duration ."'
			       , '". $start ."', '". $end ."', '". $scheduled ."', '".$slug."');";
			      $result = mysqli_query($con, $query);   
				    }
			    }
			}
// File Error reporting
			else
			{
				if ($_FILES["imageInput"]["size"] < 4000000) {
					$_SESSION['errImgSize'] = 'Maximum image size is 4 MegaBytes';
				}
				else if ($_FILES["audioInput"]["size"] < 64000000) {
					$_SESSION['errAudioSize'] = 'Maximum audio file size is 64 MegaBytes';
				}
				else
				{
					$_SESSION['errFileType'] = 'Wrong File Type. jpg, jpeg, gif, png, mp3, ogg, flak, and wav supported';
				}
			}
		}
	}
}

// Redirect after submit
	header("Location: ../../".$slug."/upload");

?>