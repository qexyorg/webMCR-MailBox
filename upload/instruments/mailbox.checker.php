<?php
if(isset($_GET['mode']) && $_GET['mode']=='mailbox'){}else{
	if(!empty($user)){
		$mb_query = BD("SELECT COUNT(*) FROM `mb_messages` WHERE uid='$player_id' AND folder='-1' AND `read`='0'");
		$mb_ar = mysql_fetch_array($mb_query);
		if($mb_ar[0]>0){ include_once(STYLE_URL.'Default/mailbox/alert.html'); }
	}
}

?>