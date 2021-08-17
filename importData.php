<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
/*
    The task spec requires me to pull data from http://mip-prd-web.azurewebsites.net/DataItemExplorer and specificly the "Calorific Value" subset of data.
    The page lets you generate CSV or XML files based on some critera, or a URL to provide you an HTML table directly. The spec wants the data "for this year so far", 
    which the Data Item Explorer page does not let you do due to the high number of records. As such, multiple queries will need to be run to get all data in 2021 so far.
    I could do this in code by using the POST endpoint "DownloadFile" that the page itself uses to generate the files, although this is undocumented and relies on 
    it not changing in future. 

    I did some additional research and found that National Grid have a public SOAP service which can return this data directly, or we could use the service
    to import into the database on a periodic basis (perhaps as a daily cron job). This service is documented here https://marketinformation.natgrid.co.uk/.
    I would be more comfortable using a documented API than reverse engineering a page. Plus. it means getting the data directly, rather than downloading 
    CSV files and importing those.
    
    An example SOAP request:
    <?xml version="1.0" encoding="utf-8"?>
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <GetPublicationDataWM xmlns="http://www.NationalGrid.com/MIPI/">
        <reqObject>
            <LatestFlag>N</LatestFlag>
            <ApplicableForFlag>Y</ApplicableForFlag>
            <ToDate>2021-08-14</ToDate>
            <FromDate>2021-08-01</FromDate>
            <DateType>gasday</DateType>
            <PublicationObjectNameList>
            <string>Calorific Value, Campbeltown</string>
            <string>Calorific Value, LDZ(EA)</string>
            </PublicationObjectNameList>
        </reqObject>
        </GetPublicationDataWM>
    </soap:Body>
    </soap:Envelope>
*/ 

//we're gonna open the connection to the MySQL server here, as always replace with your credentials or have them in enviroment variables and remove this bit
$dbuser = "databaseuser";
$dbpass = "databasepass";
$dbhost = "localhost";

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, 'nationalgridcv'); //notice that we're connecting to the specific database/schema here. Adjust if your schema is different.

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} 

//we need to add all locations to the list. comment out the ones you don't want
$locationsArray = array(
            "Calorific Value, Campbeltown",
            "Calorific Value, LDZ(EA)",
            "Calorific Value, LDZ(EM)",
            "Calorific Value, LDZ(NE)",
            "Calorific Value, LDZ(NO)",
            "Calorific Value, LDZ(NT)",
            "Calorific Value, LDZ(NW)",
            "Calorific Value, LDZ(SC)",
            "Calorific Value, LDZ(SE)",
            "Calorific Value, LDZ(SO)",
            "Calorific Value, LDZ(SW)",
            "Calorific Value, LDZ(WM)",
            "Calorific Value, LDZ(WN)",
            "Calorific Value, LDZ(WS)",
            "Calorific Value, Oban",
            "Calorific Value, Stornoway",
            "Calorific Value, Stranraer",
            "Calorific Value, Thurso",
            "Calorific Value, Wick"
            );

$soapUrl = "http://marketinformation.natgrid.co.uk/MIPIws-public/public/publicwebservice.asmx";


//since we need multiple months, this will need running monthly from the start of the year up to the current day
$periodicDate = new DateTime('2021-01-01');
$endDate = new DateTime(); //current date
while($periodicDate->format('m') <= $endDate->format('m')){
    $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>'.
    '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'.
    '<soap:Body>'.
        '<GetPublicationDataWM xmlns="http://www.NationalGrid.com/MIPI/">'.
        '<reqObject>'.
            '<LatestFlag>N</LatestFlag>'.
            '<ApplicableForFlag>Y</ApplicableForFlag>'.
            '<FromDate>'.$periodicDate->format('Y-m-d').'</FromDate>'.
            '<ToDate>'.date('Y-m-d',strtotime($periodicDate->format('Y-m-d').' +1 month')).'</ToDate>'.
            '<DateType>gasday</DateType>'.
            '<PublicationObjectNameList>';
            foreach($locationsArray as $singleLocation){
                $xml_post_string .= '<string>'.$singleLocation.'</string>';
            }
    $xml_post_string .= '</PublicationObjectNameList>'.
        '</reqObject>'.
        '</GetPublicationDataWM>'.
    '</soap:Body>'.
    '</soap:Envelope>';

    $headers = array(
    "POST /MIPIws-public/public/publicwebservice.asmx HTTP/1.1",
    "Host: marketinformation.natgrid.co.uk",
    "Content-Type: text/xml; charset=utf-8",
    "Content-Length: ".strlen($xml_post_string)
    ); 

    $url = $soapUrl;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch); 
    curl_close($ch);

    $response1 = str_replace("<soap:Body>","",$response);
    $response2 = str_replace("</soap:Body>","",$response1);

    $parser = simplexml_load_string($response2);

    //now we have the data from the API, we need to import into our database
    //since the data is grouped by location, that actually means we can add the location first and use the generated ID from that before adding all the values. Handy!

    $sql_insert_location = $mysqli->prepare("INSERT INTO areas (dataItem, areaName, subDivision) VALUES (?, ?, ?) ");
    $sql_insert_location->bind_param("sss", $dataItem, $areaName, $subDivision);

    $sql_insert_dailyvalue = $mysqli->prepare("INSERT INTO dailyvalues (areaID, applicableFor, calorificValue) VALUES (?, ?, ?) ");
    $sql_insert_dailyvalue->bind_param("sss", $areaID, $applicableFor, $calorificValue);

    //loop through each location
    foreach($parser->GetPublicationDataWMResponse->GetPublicationDataWMResult->CLSMIPIPublicationObjectBE as $locationKey=>$details){
        
        $dataItem = $details->PublicationObjectName;
        //we now split the dataitem up into parts using a regular expression
        $dividedArea = array();
        $areaRegex = "Calorific Value, (\w*)(\((\w*)\))*";
        preg_match($areaRegex, $dataItem, $dividedArea); //this puts the regex matches into $dividedArea in the bracketed groups
        //$dividedArea[0] contains the full text, [1] contains the location , such as "LDZ" or "Stranraer", [2] is optional if a subdivision is found and includes the brackets, [3] is without the brackets

        $areaName = $dividedArea[1];
        $subDivision = array_key_exists(3,$dividedArea)===true ? $dividedArea[3] : NULL; //if no subdivision, we set this to null
        $sql_insert_location->execute();

        //now we've inserted an area, we want the key/ID for the record we just inserted
        $areaID = $sql_insert_location->insert_id;

        //And now we loop through and insert the individual records
        foreach($details->PublicationObjectData->CLSPublicationObjectDataBE as $recordKey=>$dailyValueDetails){
            
            $applicableFor = $dailyValueDetails->ApplicableFor;
            $calorificValue = $dailyValueDetails->Value;
            $sql_insert_dailyvalue->execute();

        }
    }   

    //add a month to the periodic date
    $periodicDate->add(new DateInterval("P1M"));
}