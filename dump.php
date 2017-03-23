<?php 
/***********************************************
This is for the TTC App.  This will create 
the Database, Tables, and Views.
This will download the zip file, extract the
csv files, and import them to their respective 
tables.
***********************************************/

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DB', 'ttc');

$tablesArray = array(0 => 'agency', 1 => 'calendar', 2 => 'calendar_dates', 3 => 'routes', 4 => 'shapes', 5 => 'stops', 6 => 'stop_times', 7 => 'trips');
$zipFolder = 'csv';
$zipFile = 'ttc.zip';

createDatabase();
createTables();
downloadzip($zipFolder, $zipFile);
emptyTables($tablesArray);
importcsv($zipFolder, $zipFile, $tablesArray);
deleteFiles($zipFolder, $zipFile);

function downloadzip($zipFolder, $zipFile) {
	$url = 'http://opendata.toronto.ca/TTC/routes/OpenData_TTC_Schedules.zip';
	
	if (!is_dir($zipFolder)) {
		mkdir($zipFolder, 0777, true);
		echo 'successfully created ' . $zipFolder . '<br />';
	}
	
	$zipResource = fopen($zipFolder . '/' . $zipFile, "w");
	// Get The Zip File From Server
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
	curl_setopt($ch, CURLOPT_FILE, $zipResource);
    curl_setopt($ch, CURLOPT_TIMEOUT,500);
	
    $page = curl_exec($ch);
    
	if(!$page) {
	   echo 'Error :- ' . curl_error($ch);
	} else {
	   unzip($zipFolder, $zipFile);
	}
    
	curl_close($ch);
}

function unzip($zipFolder, $zipFile) {
	$zip = new ZipArchive;
	
	if($zip->open($zipFolder . '/' .$zipFile) != 'true'){
		echo 'Error :- Unable to open the Zip File';
	} else {
		echo 'successfully unzipped ' . $zipFile . ' to ' . $zipFolder . '<br/>';    
	}
	
	$zip->extractTo($zipFolder);
	$zip->close();
}

function emptyTables($tablesArray) {
	$cons= mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DB) or die(mysql_error());
	
	foreach($tablesArray as $key => $val) {
		$table = $val;
		mysqli_query($cons, 'TRUNCATE TABLE ' . $table);
	}
	echo '<br/>tables have been successfully truncated<br/><br/>';
}

function importcsv($zipFolder, $zipFile, $tablesArray) {	
	foreach($tablesArray as $key => $val) {
		$csvFile = $val . '.txt';
		$table = $val;
		$file = $zipFolder . '/' . $csvFile;

		$cons = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DB) or die(mysql_error());
		$result1 = mysqli_query($cons, 'select count(*) count from ' . $table);
		$r1 = mysqli_fetch_array($result1);
		$count1 = (int)$r1['count'];

		mysqli_query($cons, '
			LOAD DATA LOCAL INFILE "' . $file . '"
				INTO TABLE ' . $table . '
				FIELDS TERMINATED by \',\'
				LINES TERMINATED BY \'\n\'
		') or die(mysql_error());

		$result2 = mysqli_query($cons, 'select count(*) count from ' . $table);
		$r2 = mysqli_fetch_array($result2);
		$count2 = (int)$r2['count'];

		$count = $count2-$count1;
		if($count > 0) {
            echo 'Import of ' . $table . ' successfull <br/>';
            echo '<b> total ' . $count . ' records have been added to the table ' . $table . ' </b> <br/><br/>';
        }     
	}
}

function deleteFiles($zipFolder, $zipFile) {
    $files = glob($zipFolder . '/*'); //get all file names
    foreach($files as $file){
        if(is_file($file)) {
            if(unlink($file)) {
                echo '<br />' . $file . ' have been successfully deleted';
            }
        }
    }
    if(rmdir($zipFolder)) {
        echo '<br />the directory ' . $zipFolder . ' have been successfully deleted';
    }
}

function createDatabase() {
	$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
	$sql = 'CREATE DATABASE ' . DB_DB;
	
	if ($conn->query($sql) === TRUE) {
		echo 'Database created successfully<br/>';
	} else {
		echo 'Error creating database: ' . $conn->error . '<br/>';
	}
}

function createTables() {
	$cons = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DB) or die(mysql_error());
	
	mysqli_query($cons,"
		CREATE TABLE `agency` (
		  `agency_id` int(1) DEFAULT NULL,
		  `agency_name` varchar(3) DEFAULT NULL,
		  `agency_url` varchar(17) DEFAULT NULL,
		  `agency_timezone` varchar(15) DEFAULT NULL,
		  `agency_lang` varchar(2) DEFAULT NULL,
		  `agency_phone` varchar(12) DEFAULT NULL,
		  `agency_fare_url` varchar(10) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `calendar` (
		  `service_id` int(3) DEFAULT NULL,
		  `monday` int(1) DEFAULT NULL,
		  `tuesday` int(1) DEFAULT NULL,
		  `wednesday` int(1) DEFAULT NULL,
		  `thursday` int(1) DEFAULT NULL,
		  `friday` int(1) DEFAULT NULL,
		  `saturday` int(1) DEFAULT NULL,
		  `sunday` int(1) DEFAULT NULL,
		  `start_date` int(8) DEFAULT NULL,
		  `end_date` int(8) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `calendar_dates` (
		  `service_id` int(1) DEFAULT NULL,
		  `date` int(8) DEFAULT NULL,
		  `exception_type` int(1) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `routes` (
		  `route_id` int(5) NOT NULL,
		  `agency_id` int(1) DEFAULT NULL,
		  `route_short_name` varchar(3) DEFAULT NULL,
		  `route_long_name` varchar(28) DEFAULT NULL,
		  `route_desc` varchar(10) DEFAULT NULL,
		  `route_type` int(1) DEFAULT NULL,
		  `route_url` varchar(10) DEFAULT NULL,
		  `route_color` varchar(6) DEFAULT NULL,
		  `route_text_color` varchar(6) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `shapes` (
		  `ape_id` int(6) DEFAULT NULL,
		  `shape_pt_lat` decimal(8,6) DEFAULT NULL,
		  `shape_pt_lon` decimal(9,6) DEFAULT NULL,
		  `shape_pt_sequence` int(4) DEFAULT NULL,
		  `shape_dist_traveled` decimal(6,4) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `stops` (
		  `stop_id` varchar(10) NOT NULL,
		  `stop_code` varchar(10) DEFAULT NULL,
		  `stop_name` varchar(255) DEFAULT NULL,
		  `stop_desc` varchar(10) DEFAULT NULL,
		  `stop_lat` varchar(10) DEFAULT NULL,
		  `stop_lon` varchar(10) DEFAULT NULL,
		  `zone_id` varchar(10) DEFAULT NULL,
		  `stop_url` varchar(10) DEFAULT NULL,
		  `location_type` varchar(10) DEFAULT NULL,
		  `parent_station` varchar(10) DEFAULT NULL,
		  `wheelchair_boarding` varchar(10) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `stop_times` (
		  `trip_id` varchar(10) DEFAULT NULL,
		  `arrival_time` varchar(10) DEFAULT NULL,
		  `departure_time` varchar(10) DEFAULT NULL,
		  `stop_id` varchar(10) DEFAULT NULL,
		  `stop_sequence` varchar(10) DEFAULT NULL,
		  `stop_headsign` varchar(10) DEFAULT NULL,
		  `pickup_type` varchar(10) DEFAULT NULL,
		  `drop_off_type` varchar(10) DEFAULT NULL,
		  `shape_dist_traveled` varchar(10) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		CREATE TABLE `trips` (
		  `route_id` varchar(10) DEFAULT NULL,
		  `service_id` varchar(10) DEFAULT NULL,
		  `trip_id` varchar(10) DEFAULT NULL,
		  `trip_headsign` varchar(255) DEFAULT NULL,
		  `trip_short_name` varchar(10) DEFAULT NULL,
		  `direction_id` varchar(10) DEFAULT NULL,
		  `block_id` varchar(10) DEFAULT NULL,
		  `shape_id` varchar(10) DEFAULT NULL,
		  `wheelchair_accessible` varchar(10) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	mysqli_query($cons,"
		ALTER TABLE `routes`
		  ADD PRIMARY KEY (`route_id`),
		  ADD KEY `route_long_name` (`route_long_name`);
	");
	mysqli_query($cons,"
		ALTER TABLE `stops`
		  ADD PRIMARY KEY (`stop_id`),
		  ADD KEY `stop_code` (`stop_code`),
		  ADD KEY `stop_lat` (`stop_lat`),
		  ADD KEY `stop_lon` (`stop_lon`);
	");
	mysqli_query($cons,"
		ALTER TABLE `stop_times`
		  ADD KEY `trip_id` (`trip_id`),
		  ADD KEY `stop_id` (`stop_id`);
	");
	mysqli_query($cons,"
		ALTER TABLE `trips`
		  ADD KEY `route_id` (`route_id`);
	");
	mysqli_query($cons,"
		CREATE VIEW bus_view AS
		select `ttc`.`routes`.`route_id` AS `route_id`,`ttc`.`routes`.`route_long_name` AS `route_long_name`,`ttc`.`routes`.`route_short_name` AS `route_short_name` from `ttc`.`routes`
	");
	mysqli_query($cons,"
		CREATE VIEW route_view AS
		select `r`.`route_long_name` AS `route_long_name`,`r`.`route_short_name` AS `route_short_name`,`ttc`.`trips`.`route_id` AS `route_id`,`ttc`.`trips`.`trip_id` AS `trip_id`,`ttc`.`trips`.`trip_headsign` AS `trip_headsign`,`st`.`stop_id` AS `stop_id`,`st`.`arrival_time` AS `arrival_time`,`st`.`departure_time` AS `departure_time`,`s`.`stop_name` AS `stop_name`,`s`.`stop_lat` AS `stop_lat`,`s`.`stop_lon` AS `stop_lon` from (((`ttc`.`trips` left join `ttc`.`routes` `r` on((`r`.`route_id` = `ttc`.`trips`.`route_id`))) left join `ttc`.`stop_times` `st` on((`ttc`.`trips`.`trip_id` = `st`.`trip_id`))) left join `ttc`.`stops` `s` on((`st`.`stop_id` = `s`.`stop_id`)))
	");
}
?>