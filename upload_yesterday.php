<?php
error_reporting(E_ALL);
/** script version v1.0 **/
/************ FILL IN YOUR MyHomeEnergy DETAILS HERE **********/
$my_email='';
$my_pass='';
/**************************************************************/

/************ FILL IN YOUR PVOutput DETAILS HERE **************/
$PVOutput_APIKey = '';
$PVOutput_SystemID = '';
/**************************************************************/

$my_hashed_pass=hash('sha256', $my_email.':'.hash('md5', $my_pass));

$r=makeRequest('GWRLogin','%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Cemail%3E'.urlencode($my_email).'%3C%2Femail%3E%3Cpassword%3E'.$my_hashed_pass.'%3C%2Fpassword%3E%3C%2Fgip%3E');
$o=json_decode($r, true);
//get token
$token=$o['gip']['token'];

//get meters
$r2=makeRequest('GWRBatch', '%3Cgwrcmds%3E%3Cgwrcmd%3E%3Cgcmd%3ESPA_UserGetSmartMeterList%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E%3C%2Fgwrcmds%3E');
$o=json_decode($r2, true);
$meters=array();
$metersr = $o['gwrcmds']['gwrcmd']['gdata']['gip']['meter'];
for($i=0;$i<count($metersr);$i++)
   if($metersr[$i]['status']!='-1' && $metersr[$i]['enabled']=='1')
   {
      $meters[]=$metersr[$i];
	}

//request yesterday data

$fd=date('Ymd', strtotime(date('Y-m-d H:i:s') . ' - 1 day')).'000000';
$td=date('Ymd').'000000';
$req='';
$mdata=array();
for($i=0;$i<count($meters);$i++)
{
    $req='%3Cgwrcmd%3E%3Cgcmd%3EDeviceGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cdid%3E'.$meters[$i]['did'].'%3C%2Fdid%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cislocal%3E1%3C%2Fislocal%3E%3Cfeed%3Eenergyperhalfhour%3C%2Ffeed%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
    $r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
	$m=json_decode($r3, true);
	$mdata[]=$m['gwrcmds']['gwrcmd']['gdata']['gip']['chart'];
}
//data
$req='%3Cgwrcmd%3E%3Cgcmd%3EUserGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cfeed%3Eenergyperhalfhour%2Ctempout%3C%2Ffeed%3E%3Cislocal%3E1%3C%2Fislocal%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
$r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
$tdata=json_decode($r3, true);
$min_temp=10000;
$max_temp=0;
$imported=0;
$exported=0;
for($i=0;$i<count($meters);$i++)
{
   

   $c=0;
   $data =explode(',',$mdata[$i]['energyperhalfhour']);
   for($j=1;$j<count($data);$j+=2)
   {
	  if (strpos($metersr[$i]['name'],'House')!==false)
         $imported+=$data[$j];
	  if (strpos($metersr[$i]['name'],'Solar')!==false)
	     $exported+=$data[$j];
   }

   if (strpos($metersr[$i]['name'],'House')!==false && array_key_exists('tempout', $mdata[$i]))
   {
	   $data =explode(',',$mdata[$i]['tempout']);
	   for($j=1;$j<count($data);$j+=2)
	   {
		  if ($data[$j]<$min_temp)
			 $min_temp = $data[$j];
		  if ($data[$j]>$max_temp)
			 $max_temp = $data[$j];
	   }
   }
}
if ($min_temp==10000) $min_temp=='';
if ($max_temp==0) $max_temp=='';
echo uploadDaily($PVOutput_APIKey, $PVOutput_SystemID, $exported*1000, $imported*1000, $min_temp, $max_temp);

function uploadDaily($apiKey, $systemID, $exported, $imported, $minTemp, $maxTemp) {
   $url = 'http://pvoutput.org/service/r2/addoutput.jsp';

   $fields = array(
		'd' => urlencode(date('Ymd', strtotime(date('Y-m-d H:i:s') . ' - 1 day'))),
		'g' => urlencode('0'), //required if output does not already exist
		'e' => urlencode($exported),
		'cm' => urlencode('Imported from MyHomeEnergy'),
		'ip' => urlencode($imported)
	);
	if ($minTemp!='' && $maxTemp!='') {
	   $fields['tm'] = urlencode($minTemp);
	   $fields['tx'] = urlencode($maxTemp);
	}
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

function makeRequest($cmd, $query) {
   $url = 'https://myhomeenergy.com.au/gwr/gop.php?cmd='.$cmd.'&fmt=json&data='.$query;
   
   $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_REFERER, 'https://myhomeenergy.com.au/ui/GWRContent.swf?versionNumber=v2.0.318');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
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

?>