<!doctype html>
<html>
<head>
<title>Meter data for today</title>
<meta name="viewport" content="width=280">
</head>
<body>
<?php
error_reporting(E_ALL);
/** script version v1.0 **/
/************ FILL IN YOUR MyHomeEnergy DETAILS HERE **********/
$my_email='';
$my_pass='';
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

	echo 'Today (delayed)<br />';
//request today data

$fd=date('Ymd').'000000';
$td=date('Ymd', strtotime(date('Y-m-d H:i:s') . ' + 1 day')).'000000';
$req='';
$mdata=array();
for($i=0;$i<count($meters);$i++)
{
   $req='%3Cgwrcmd%3E%3Cgcmd%3EDeviceGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cdid%3E'.$meters[$i]['did'].'%3C%2Fdid%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cislocal%3E1%3C%2Fislocal%3E%3Cfeed%3Eenergyperhalfhour%2Ccostperhalfhour%3C%2Ffeed%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
   $r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
$m=json_decode($r3, true);
$mdata[]=$m['gwrcmds']['gwrcmd']['gdata']['gip']['chart'];
   }
//temperature data
$req='%3Cgwrcmd%3E%3Cgcmd%3EUserGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cfeed%3Eenergyperhalfhour%2Ccostperhalfhour%2Ctempout%3C%2Ffeed%3E%3Cislocal%3E1%3C%2Fislocal%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
$r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
$tdata=json_decode($r3, true);

for($i=0;$i<count($meters);$i++)
{
   echo $metersr[$i]['name'].': ';
   $e=0;
   $c=0;
   $data =explode(',',$mdata[$i]['energyperhalfhour']);
   for($j=1;$j<count($data);$j+=2)
   {
      $e+=$data[$j];
   }
   $data =explode(',',$mdata[$i]['costperhalfhour']);
   for($j=1;$j<count($data);$j+=2)
   {
      $c+=$data[$j];
   }
   echo $e.' KW / $'.$c.'<br />';
}

echo 'Yesterday<br />';

//request yesterday data

$fd=date('Ymd', strtotime(date('Y-m-d H:i:s') . ' - 1 day')).'000000';
$td=date('Ymd').'000000';
$req='';
$mdata=array();
for($i=0;$i<count($meters);$i++)
{
   $req='%3Cgwrcmd%3E%3Cgcmd%3EDeviceGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cdid%3E'.$meters[$i]['did'].'%3C%2Fdid%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cislocal%3E1%3C%2Fislocal%3E%3Cfeed%3Eenergyperhalfhour%2Ccostperhalfhour%3C%2Ffeed%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
   $r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
$m=json_decode($r3, true);
$mdata[]=$m['gwrcmds']['gwrcmd']['gdata']['gip']['chart'];
   }
//temperature data
$req='%3Cgwrcmd%3E%3Cgcmd%3EUserGetChart%3C%2Fgcmd%3E%3Cgdata%3E%3Cgip%3E%3Cversion%3E1%3C%2Fversion%3E%3Ctoken%3E'.$token.'%3C%2Ftoken%3E%3Cfd%3E'.$fd.'%3C%2Ffd%3E%3Ctd%3E'.$td.'%3C%2Ftd%3E%3Cfeed%3Eenergyperhalfhour%2Ccostperhalfhour%2Ctempout%3C%2Ffeed%3E%3Cislocal%3E1%3C%2Fislocal%3E%3C%2Fgip%3E%3C%2Fgdata%3E%3C%2Fgwrcmd%3E';
$r3=makeRequest('GWRBatch', '%3Cgwrcmds%3E'.$req.'%3C%2Fgwrcmds%3E');
$tdata=json_decode($r3, true);

for($i=0;$i<count($meters);$i++)
{
   echo $metersr[$i]['name'].': ';
   $e=0;
   $c=0;
   $data =explode(',',$mdata[$i]['energyperhalfhour']);
   for($j=1;$j<count($data);$j+=2)
   {
      $e+=$data[$j];
   }
   $data =explode(',',$mdata[$i]['costperhalfhour']);
   for($j=1;$j<count($data);$j+=2)
   {
      $c+=$data[$j];
   }
   echo $e.' KW / $'.$c.'<br />';
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
//echo 'header: '.$header;
if(curl_errno($ch))
   echo 'err: '.curl_error($ch);
curl_close($ch);
return $body;
}

?>
</body>
</html>