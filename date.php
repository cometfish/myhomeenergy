<?php
error_reporting(E_ALL);
/** script version v1.0 **/
/************ FILL IN YOUR MyHomeEnergy DETAILS HERE **********/
$aspxauthtxtfile = 'aspxauth.txt';     //paste the alphanumeric ASPXAUTH cookie data into this file
$nmi='';
/**************************************************************/

/************ FILL IN YOUR PVOutput DETAILS HERE **************/
$PVOutput_APIKey = '';
$PVOutput_SystemID = '';
/**************************************************************/

/* example usage:
upload_date.php?date=20200131 - uploads MyHomeEnergy data from 31 Jan 2020 to PVOutput
upload_date.php?date=20200131&autoupload=45 - uploads MyHomeEnergy data from 31 Jan 2020 to PVOutput, then progresses on to upload the next day's data 45s later (spaced out to avoid using up all your PVOutput API calls for the hour)
*/
$htmlheader = '<!doctype html>
<html>
<head>
<title>Meter data for '.$_GET['date'].'</title>
<link rel="icon" href="data:;base64,iVBORw0KGgo=">
<meta name="viewport" content="width=280">';
$htmlheadermeta = '';
$htmlheaderend = '</head>
<body>';
$htmloutput = '';
$htmlfooter = '
</body>
</html>';

$fd=$_GET['date'];
$td=$_GET['date'];
$r=makeRequest($fd,$td,$nmi,$aspxauthtxtfile);
$o=json_decode($r, true);
$data=$o['Series'];
$imported_peak=0;
$imported_offpeak=0;
$exported=0;
for($i=0;$i<count($data);$i++)
{
    $amt = $data[$i]['data'][0];
    if ($data[$i]['name']=='Export Solar') {
        $exported=$amt*-1000;
    } else if ($data[$i]['name']=='Consumption Peak') {
        $imported_peak=$amt*1000;
    }  else if ($data[$i]['name']=='Consumption Off Peak') {
        $imported_offpeak=$amt*1000;
    }
}
$htmloutput .= 'Date: '.date('Y-m-d', strtotime($_GET['date'])).'<br/>';
$htmloutput .= 'E: '.$exported.' P: '.$imported_peak.' O: '.$imported_offpeak.'<br />';
if ($exported==0 && $imported_offpeak==0 && $imported_peak==0) {
    $htmloutput .= 'No values returned from MyHomeEnergy';
    printhtml($htmlheader, $htmlheadermeta, $htmlheaderend, $htmloutput, $htmlfooter);
    return;
}

$pvouploadres = uploadDaily($PVOutput_APIKey, $PVOutput_SystemID, $_GET['date'], $exported, $imported_peak, $imported_offpeak);
$htmloutput .= $pvouploadres;

if (isset($_GET['autoupload']) && substr($pvouploadres,0,2)=='OK' && strtotime($_GET['date'])<strtotime('yesterday')) {
    $htmlheadermeta .= '<meta http-equiv="refresh" content="'.$_GET['autoupload'].'; url=upload_date.php?autoupload='.$_GET['autoupload'].'&date='.date('Ymd', strtotime($_GET['date'].' + 1 day')).'">';
}

$htmloutput .= '<br /><br /><a href="?date='.date('Ymd', strtotime($_GET['date'].' - 1 day')).'">Previous</a> | <a href="?date='.date('Ymd', strtotime($_GET['date'].' + 1 day')).'">Next</a>';

printhtml($htmlheader, $htmlheadermeta, $htmlheaderend, $htmloutput, $htmlfooter);

function uploadDaily($apiKey, $systemID, $date, $exported, $imported_peak, $imported_offpeak) {
   $url = 'http://pvoutput.org/service/r2/addoutput.jsp';

   $fields = array(
		'd' => urlencode(date('Ymd', strtotime($date))),
		//'g' => urlencode('0'), //required if output does not already exist
		'e' => urlencode($exported),
		'cm' => urlencode('Imported from MyHomeEnergy'),
		'ip' => urlencode($imported_peak),
        'io' => urlencode($imported_offpeak)
	);
    $fields_string = '';
	foreach($fields as $key=>$value)
		$fields_string .= $key.'='.$value.'&';
	rtrim($fields_string, '&');
   
   $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER,array(
		'Content-Type: application/x-www-form-urlencoded',
		'X-Pvoutput-Apikey: '.$apiKey,
		'X-Pvoutput-SystemId: '.$systemID
	));
	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		
	// Get the response and close the channel.
	$response = curl_exec($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	if(curl_errno($ch))
	   echo 'err: '.curl_error($ch);
	curl_close($ch);
	return $body;
}

function makeRequest($startDate, $endDate, $nmi, $aspxauthtxtfile) {
   $aspxauth=file_get_contents($aspxauthtxtfile);
   $url = 'https://www.ausnetservices.com.au/api/Sitecore/Dashboard/GetDataForChart?customerNMI='.$nmi.'&startdate='.$startDate.'&enddate='.$endDate.'&chartView=W&typeOfData=netUsage&timeSlicesArray=[{%22START_DT%22%3A%222017-07-31%22%2C%22END_DT%22%3A%22Current%22}]&rateConFlat=0';
   
   $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.ausnetservices.com.au/en/myHomeEnergy/Dashboard');
    curl_setopt($ch,CURLOPT_COOKIE, '.ASPXAUTH='.$aspxauth.';');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Set so curl_exec returns the result instead of outputting it.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
    // Get the response and close the channel.
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    //echo 'header: '.$header;
    if(curl_errno($ch))
       echo 'err: '.curl_error($ch);
    //check if we were given a new .ASPXAUTH cookie
    preg_match_all("/^Set-Cookie:\s+.ASPXAUTH=(.*);/mU", $header, $cookieMatchArray);
    if (count($cookieMatchArray)>1 && count($cookieMatchArray[1])>0)
    {
        //save the new cookie for the next request
        file_put_contents($aspxauthtxtfile, $cookieMatchArray[1]);
    }
    curl_close($ch);
    return $body;
}

function printhtml($header, $meta, $headerend, $content, $footer) {
    echo $header;
    echo $meta;
    echo $headerend;
    echo $content;
    echo $footer;
}