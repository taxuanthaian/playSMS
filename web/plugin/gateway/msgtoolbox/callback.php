<?php

if (! $called_from_hook_call) {
	chdir("../../../");
	include "init.php";
	include $apps_path['libs']."/function.php";
	chdir("plugin/gateway/msgtoolbox/");
	$requests = $_REQUEST;
}

$cb_smsid = $requests['smsid'];
$cb_status = $requests['status'];

/*
 $fc = "smsid: $cb_smsid status: $cb_status\n";
 $fn = "/tmp/msgtoolbox_callback";
 umask(0);
 $fd = fopen($fn,"a+");
 fputs($fd,$fc);
 fclose($fd);
 die();
 */

if ($cb_status && $cb_smsid)
{
	$db_query = "
		SELECT local_slid FROM "._DB_PREF_."_gatewayMsgtoolbox 
		WHERE remote_slid='$cb_smsid' AND (status='10' OR status='11' OR status='21')
	";
	$db_result = dba_query($db_query);
	$db_row = dba_fetch_array($db_result);
	$smslog_id = $db_row['local_slid'];
	if ($smslog_id)
	{
		$data = getsmsoutgoing($smslog_id);
		$uid = $data['uid'];
		$c_sms_status = $data['p_status'];
		switch ($cb_status)
		{
			case "10": 
			case "11":
			case "21": $c_sms_status = 1; break; // sent
			case "30":
			case "41":
			case "42":
			case "44":
			case "50": $c_sms_status = 2; break; // failed
			case "22": $c_sms_status = 3; break; // delivered
		}
		// default is pending
		$p_status = 0;
		if ($c_sms_status)
		{
			$p_status = $c_sms_status;
		}
		setsmsdeliverystatus($smslog_id,$uid,$p_status);
		
		ob_end_clean();
		echo 'OK'; // must response with unformated text OK according to msgtoolbox API
		exit();
	}
}

?>