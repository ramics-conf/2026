<?php

$SPREADSHEET_ID = '1Uwi6x-IMmzncHSeFl8Vr6VsVISm3uDjsft9ieA5Nu64';


//$url='https://docs.google.com/spreadsheets/d/1Uwi6x-IMmzncHSeFl8Vr6VsVISm3uDjsft9ieA5Nu64/edit?usp=sharing';

$SHEET_NAME= urlencode("Candidates for PC");
$QUERY=urlencode("SELECT A,B,F WHERE M = 'Yes' ORDER BY B");

$url="https://docs.google.com/spreadsheets/d/$SPREADSHEET_ID/gviz/tq?sheet=$SHEET_NAME&tq=$QUERY";

$response = file_get_contents($url);

//print_r($response);exit;

// Google restituisce JSONP, va "ripulito"
$jsonData = substr($response, 47, -2);
$data = json_decode($jsonData, true);

// Estrai righe
$rows = $data['table']['rows'];

// Stampa i risultati
foreach ($rows as $row) {
    $prenom = $row['c'][0]['v'] ?? '';
    $nom = $row['c'][1]['v'] ?? '';
    $webpage = $row['c'][2]['v'] ?? '';
    $role='';
    if(in_array($nom,['Fussner','Fahrenberg','Santocanale'])){
        $role ='(co-chair)';
    }
    echo "<a href=\"$webpage\">$prenom $nom $role</a>\n";
}