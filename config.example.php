<?php
// Compy this file to config.php and change the $tidbit_dir and $sugar_dir according to your files location

$tidbit_dir = realpath(dirname(__FILE__));
//set this to the SugarCE installation where the package will be deployed
//it is the directory that contains 'index.php'
$sugar_dir = $tidbit_dir . '/..';

//go to the top of the SugarCE directory
chdir($sugar_dir);
?>
