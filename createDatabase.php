<?php
/*
    This script will connect to the MySQL/MariaDB database and check if the "nationalgridcv" schema exists, and create it if not. 
    Then it will create (if not already existing) two tables, `areas` and `dailyvalues`. 
    That's it. This is just a pre-data import setup file just to make sure you're ready.
*/

//Step 1: Connect to the database, put your own credentials in here or, better yet, have them as environment variables somewhere else so they're not in the code. 
$dbuser = "databaseuser";
$dbpass = "databasepass";
$dbhost = "localhost";

$mysqli = new mysqli($dbhost, $dbuser, $dbpass);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} 

//We're connected, now we create the schema/database

$sql_dbcreate = "CREATE DATABASE nationalgridcv";
if ($conn->query($sql_dbcreate) === TRUE) {
  echo "Database created successfully";
} else {
  die("Error creating database: " . $conn->error);
}

//and connect to it
$mysqli->select_db('nationalgridcv');

//Next, make the tables
/* 
  In the provided task spec, I was told the areas are displayed as "Calorific Value, LDZ(<area>)" such as "Calorific Value, LDZ(SW)". 
  To store this more efficiently for indexing purposes, I am splitting out this information into the constituant parts, with the "areaName" column being "LDZ" 
  and the "subdivision" column being the code in brackets, such as "SW".
  However, there are several areas that are not in that format, such as "Calorific Value, Oban". It wasn't made clear if these were meant to be ignored and/or if they
  could be included in future. As such, "subdivision" is an optional column, with "areaName" containing the non-bracketed part, such as "Oban". 

*/
$sql_tblcreate_areas = "CREATE TABLE `areas` (
  `areaID` int NOT NULL AUTO_INCREMENT,
  `dataItem` varchar(45) NOT NULL,
  `areaName` varchar(45) NOT NULL,
  `subdivision` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`areaID`),
  UNIQUE KEY `id_UNIQUE` (`areaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";
$mysqli->query($sql_tblcreate_areas);

/*
  Note that dailyvalues has a foreign key connecting it to the areas table.
  I'm not super familiar with the data, but with the data I currently have access to, it's possible that instead of using a "dailyID" auto increment key, 
  we could use a compound key across areaID and applicableFor, since it seems there's only ever one CV per day per area. I didn't want to make this assumption,
  so I stuck with using an auto increment column instead.
*/
$sql_tblcreate_dailyvalues = "CREATE TABLE `dailyvalues` (
  `dailyID` int NOT NULL AUTO_INCREMENT,
  `areaID` int NOT NULL,
  `applicableFor` date NOT NULL,
  `calorificValue` decimal(10,4) NOT NULL,
  PRIMARY KEY (`dailyID`),
  UNIQUE KEY `dailyID_UNIQUE` (`dailyID`),
  KEY `cvtoareaid_idx` (`areaID`) /*!80000 INVISIBLE */,
  KEY `dailyValuesDate` (`applicableFor`),
  CONSTRAINT `cvtoareaid` FOREIGN KEY (`areaID`) REFERENCES `areas` (`areaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";

$mysqli->query($sql_tblcreate_dailyvalues);

//And that's it! Database and tables are set up and ready to get imported into. 
//Now go to the file importData.php to pull data from National Grid.