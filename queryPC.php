<?php

//$url='https://docs.google.com/spreadsheets/d/1Uwi6x-IMmzncHSeFl8Vr6VsVISm3uDjsft9ieA5Nu64/edit?usp=sharing';

class QueryPC {

    const SPREADSHEET_ID = '1Uwi6x-IMmzncHSeFl8Vr6VsVISm3uDjsft9ieA5Nu64';
    const SHEET_NAME = "Candidates for PC";

    static function query($query, $debug = false) {

        $SPREADSHEET_ID = self::SPREADSHEET_ID;
        $SHEET_NAME = urlencode(self::SHEET_NAME);
        $QUERY = urlencode($query);

        $url = "https://docs.google.com/spreadsheets/d/$SPREADSHEET_ID/gviz/tq?sheet=$SHEET_NAME&tq=$QUERY";

        if ($debug) {
            print_r($url);
            exit;
        }

        $response = file_get_contents($url);

        if ($debug) {
            print_r($response);
            exit;
        }

        // Google restituisce JSONP, va "ripulito"
        $jsonData = substr($response, 47, -2);
        $data = json_decode($jsonData, true);

        // Estrai righe
        $rows = $data['table']['rows'];

        return $rows;
    }

    static function PCtoHtml() {
        $lines = [];
        $rows = self::query("SELECT A,B,F WHERE M = 'Yes' ORDER BY B");
        foreach ($rows as $row) {
            $prenom = $row['c'][0]['v'] ?? '';
            $nom = $row['c'][1]['v'] ?? '';
            $webpage = $row['c'][2]['v'] ?? '';
            $role = '';
            if (in_array($nom, ['Fussner', 'Fahrenberg', 'Santocanale'])) {
                $role = '(co-chair)';
            }
            array_push($lines, "<a href=\"$webpage\">$prenom $nom $role</a>");
        }
        return implode("\n", $lines);
    }

    static function echoPC() {
        echo self::PCtoHtml();
    }

    static function echoPCbyCountry() {
        $rows = self::query("SELECT H,Count(H) WHERE M = 'Yes' GROUP BY H ORDER BY H");
        foreach ($rows as $row) {
            $country = $row['c'][0]['v'] ?? '';
            $no = $row['c'][1]['v'] ?? '';
            echo "$country: $no\n";
        }
    }
}

//QueryPC::echoPCbyCountry();
