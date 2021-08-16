<?php
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
     
*/

try {
    $client = new SoapClient('http://marketinformation.natgrid.co.uk/MIPIws-public/public/publicwebservice.asmx?wsdl');
    $value = array('LatestFlag' => 'N',
        'ApplicableForFlag' => 'Y',
        'FromDate' => '2021-08-01',
        'ToDate' => '2021-08-14',
        'DateType' => "GASDAY",
        //the following list of object names are taken from the API data item list Excel file provided: https://www.nationalgridgas.com/document/128251/download
        'PublicationObjectNameList' => array(
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
            )
    );
    $result = $client->GetPublicationDataWM($value);

    
}
catch (SoapFault $e){
    echo $e->getMessage();
}