<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

/*
    This page will display the data we've retrieved from the National Grid API and imported into our database.
    It doesn't make sense to display all data from all time at once, so I'm including the "DataTables" jQuery plugin to add some basic features to the table, such as pagination and filtering.
    As the dataset grows, DataTables could be linked to AJAX requests to paginate on the server-side rather than locally.
*/

//we're gonna open the connection to the MySQL server here, as always replace with your credentials or have them in enviroment variables and remove this bit
$dbuser = "databaseuser";
$dbpass = "databasepass";
$dbhost = "localhost";

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, 'nationalgridcv'); //notice that we're connecting to the specific database/schema here. Adjust if your schema is different.

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calorific Values</title>
    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready( function () {
            $('#table_id').DataTable();
        } );
    </script>
</head>
<body>
    <table id="table_id" class="display">
        <thead>
            <tr>
                <th>Applicable For</th>
                <th>Value</th>
                <th>Area</th>
            </tr>
        </thead>
        <tbody>
            <?php
                //Here, we need to get the data from the database. For now, we're not doing any additional filters in the SQL query
                //as that can be done with DataTables on the user side. In future, additional filters can be added at the bottom of the query.
                $sql_selectDailyValues = "SELECT dailyvalues.applicableFor, dailyvalues.calorificValue, areas.dataItem ".
                    "FROM dailyvalues INNER JOIN areas ON dailyvalues.areaID = areas.areaID ".
                    "WHERE 1=1 "; 
                //putting 1=1 is a habit I've got to make debugging SQL queries easier as it allows all actual parts of the WHERE clause to be commented/uncommented easily
                
                $sql_resultDailyValues = $mysqli->query($sql_selectDailyValues);
                while($row = $sql_resultDailyValues->fetch_assoc()){
                    echo "<tr>
                        <td>{$row['applicableFor']}</td>
                        <td>{$row['calorificValue']}</td>
                        <td>{$row['dataItem']}</td>
                    </tr>";
                }
                ?>
            
        </tbody>
    </table>
</body>
</html>
<html>

