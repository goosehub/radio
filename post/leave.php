<?php

// Used to destroy channel sessions, but not codeigniter sessions

	echo '<br><br><br><br><center><font size="400px">bye</font></center>';
session_start();
//logout
    session_destroy();
	// unset($_SESSION['name']);
    echo '<META HTTP-EQUIV="Refresh" Content="0; URL=../">';
?>