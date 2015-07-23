<?php
/**
 * MailBox module for WebMCR
 *
 * Ajax class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 3.0.0
 *
 */

// Check Qexy constant
if (!defined('QEXY')){ exit("Hacking Attempt!"); }

class module{

	// Set default variables
	private $user			= false;
	private $db				= false;
	private $api			= false;
	private	$cfg			= array();
	private $mcfg			= array();

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		//$this->mcfg			= $this->api->getMcrConfig();

	}

	private function reply($message="", $type=false, $data=array()){

		$data = array(
			"message" => $message,
			"type" => $type,
			"data" => $data,
		);

		echo json_encode($data);

		exit();
	}

	private function get_folders(){
		$query = $this->db->query("SELECT id, title FROM `qx_mb_folders` WHERE uid='{$this->user->id}' OR `system`='1'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->reply("Empty", true); }

		$elements = array();

		while($ar = $this->db->get_row($query)){
			$elements[] = array(
				"id" => intval($ar['id']),
				"title" => $this->db->HSC($ar['title'])
			);
		}

		$this->reply("Folders has been received", true, $elements);
	}

	private function get_login(){
		if(!isset($_POST['query']) || empty($_POST['query'])){ return $this->reply('Ajax is not set'); }

		$login = $this->db->safesql($_POST['query']);

		$this->mcfg			= $this->api->getMcrConfig();
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `{$bd_users['login']}`
									FROM `{$bd_names['users']}`
									WHERE `{$bd_users['login']}` LIKE '%$login%'
									ORDER BY `{$bd_users['login']}` ASC
									LIMIT 10");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->reply('Empty result'); }

		$data = array();

		while($ar = $this->db->get_row($query)){

			$data[] = $this->db->HSC($ar[$bd_users['login']]);

		}

		return $this->reply('Result has been received', true, $data);
	}

	private function topic_move(){

		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->reply("Ajax is not set"); }

		if(!isset($_POST['ids']) || empty($_POST['ids']) || !isset($_POST['fid']) || empty($_POST['fid'])){
			$this->reply("Ajax is not set");
		}

		$fid = intval(@$_POST['fid']);

		$ids = explode(',', $_POST['ids']);

		$ids = $this->api->filter_array_integer($ids);
		$ids = implode(', ', $ids);

		$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_folders` WHERE id='$fid' AND (uid='{$this->user->id}' OR `system`='1') AND id!='3'");

		if(!$query){ $this->reply("Access Denied!"); }

		$ar = $this->db->get_array($query);

		if($ar[0]<=0){ $this->reply("Access Denied!"); }

		$move = $this->db->query("UPDATE `qx_mb_topics_links` SET fid='$fid' WHERE id IN ($ids) AND uid='{$this->user->id}'");

		if(!$move){ $this->reply("Error Query"); }

		$this->reply("Выбранные элементы успешно перемещены", true);
	}

	private function topic_close(){ // POST
		$link = intval(@$_POST['tid']);

		$query = $this->db->query("SELECT `t`.`closed`, `t`.id
									FROM `qx_mb_topics_links` AS `l`
									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.`id`=`l`.`tid`
									WHERE `l`.id='$link' AND `l`.uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->reply("Access Denied!"); }

		$ar = $this->db->get_row($query);

		if(intval($ar['closed'])==1){ $this->reply("Переписка уже закрыта!"); }

		$tid = intval($ar['id']);

		$time = time();

		$update = $this->db->query("UPDATE `qx_mb_topics` SET `closed`='1', `date_update`='$time', `closed_by`='{$this->user->id}' WHERE id='$tid' AND `closed`='0'");
		if(!$update){ $this->reply("Base Error!"); }
		
		if($this->db->get_affected_rows()<=0){ $this->reply("Переписка не закрыта!"); }
		
		$this->reply("Переписка успешно закрыта", true);
	}

	private function get_reply($topic=false){ // POST
		$id = intval(@$_POST['rid']);

		$sql = "SELECT `r`.`text_bb`
				FROM `qx_mb_reply` AS `r`
				INNER JOIN `qx_mb_topics_links` AS `l`
					ON `l`.`tid`=`r`.`tid` AND `l`.uid='{$this->user->id}'
				WHERE `r`.id='$id'";

		if($topic){
			$sql = "SELECT `t`.`text_bb`
					FROM `qx_mb_topics_links` AS `l`
					INNER JOIN `qx_mb_topics` AS `t`
						ON `t`.`id`=`l`.`tid`
					WHERE `l`.id='$id' AND `l`.uid='{$this->user->id}'";
		}

		$query = $this->db->query($sql);

		if(!$query || $this->db->num_rows($query)<=0){ $this->reply("Access Denied!"); }

		$ar = $this->db->get_row($query);

		$data = array(
			'text' => $this->db->HSC($ar['text_bb']),
		);
		
		$this->reply("Success", true, $data);
	}

	public function _list(){
		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'get_folders':		$this->get_folders(); break;
			case 'topic_move':		$this->topic_move(); break;
			case 'topic_close':		$this->topic_close(); break;
			case 'get_login':		$this->get_login(); break;
			case 'get_reply':		$this->get_reply(); break;
			case 'get_topic':		$this->get_reply(true); break;

			default: $this->reply("Ajax is not set"); break;
		}
	}
}

/**
 * MailBox module for WebMCR
 *
 * Ajax class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 3.0.0
 *
 */
?>