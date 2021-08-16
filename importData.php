<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
            '<ToDate>'.$periodicDate->format('Y-m-d').'</ToDate>'.
            '<FromDate>'.strtotime($periodicDate->format('Y-m-d').' +1 month').'</FromDate>'.
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
    

    //add a month to the periodic date
    $periodicDate->add(new DateInterval("P1M"));
}