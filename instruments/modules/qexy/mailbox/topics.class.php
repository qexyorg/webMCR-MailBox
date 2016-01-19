<?php
/**
 * MailBox module for WebMCR
 *
 * Topics class
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
	public	$title			= '';
	public	$bc				= '';
	private	$cfg			= array();
	private $csrf_name		= 'ref_main';
	private $mcfg			= array();
	private $fid			= 0;
	private $url_us;

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= $this->api->getMcrConfig();
		$this->url_us		= BASE_URL.'?mode=users&uid=';

	}

	private function reply_array($tid){

		$start		= $this->api->pagination($this->cfg['rop_reply'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_reply']; // Set end pagination
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `r`.id, `r`.tid, `r`.uid, `r`.`text_html`, `r`.`data`,
											`u`.`{$bd_users['login']}`
											#`g`.`name` AS `group`
									FROM `qx_mb_reply` AS `r`
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`r`.uid
									#LEFT JOIN `{$bd_names['groups']}` AS `g`
										#ON `g`.id=`u`.`group`
									WHERE `r`.tid='$tid'
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			
			$json = json_decode($ar['data']);

			$login = $this->db->HSC($ar[$bd_users['login']]);

			$data = array(
				'ID' => intval($ar['id']),
				'TID' => $tid,
				'UID' => intval($ar['uid']),
				'TEXT' => $ar['text_html'],
				'DATE_CREATE' => date("d.m.Y в H:i:s", $json->date_create),
				'DATE_UPDATE' => date("d.m.Y в H:i:s", $json->date_update),
				'LOGIN' => $login,
				'LOGIN_URL' => ($this->cfg['use_us']) ? $this->url_us.$login : '#',
				//'GROUP' => $this->db->HSC($ar['group']),
			);

			echo $this->api->sp("topics/reply-id.html", $data);
		}

		return ob_get_clean();
	}

	private function reply_add(){
		$link = intval($_GET['tid']);
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];
		$config			= $this->mcfg['config'];

		$query = $this->db->query("SELECT `l`.`id`, `l`.`tid`, `u`.`{$bd_users['group']}`, `u`.`{$bd_users['email']}`
									FROM `qx_mb_topics_links` AS `l`
									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.`id`=`l`.`tid` AND `t`.`closed`='0'
									LEFT JOIN `qx_mb_topics_links` AS `lt`
										ON `l`.`id`='$link' AND `lt`.`uid`!=`l`.`uid`
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`lt`.`uid`
									WHERE `l`.`id`='$link' AND `l`.`uid`='{$this->user->id}'");
		
		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Доступ запрещен!", "&do=folders", "403", 3); }

		$ar = $this->db->get_row($query);

		$tid = intval($ar['tid']);

		$email = $ar[$bd_users['email']];

		$text_bb	= $this->db->safesql($this->db->HSC(@$_POST['text']));
		$text_html	= $this->api->bb_decode(@$_POST['text']);
		$text_html	= $this->db->safesql(trim($text_html));

		if(empty($text_html)){ $this->api->notify("Не заполнен текст ответа", "&do=topics&op=view&tid=$link", "Ошибка!", 4); }

		$time = time();

		// Set data information for reply +
		$data_reply = array(
			"date_create" => $time,
			"date_update" => $time
		);

		$data_reply = $this->db->safesql(json_encode($data_reply));
		// Set data information for reply -

		$insert = $this->db->query("INSERT INTO `qx_mb_reply`
										(tid, uid, text_bb, text_html, `data`)
									VALUES
										('$tid', '{$this->user->id}', '$text_bb', '$text_html', '$data_reply')");
		if(!$insert){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=topics&op=view&tid=$link", "Внимание!", 3); }

		$update = $this->db->query("UPDATE `qx_mb_topics_links` SET `read`='0' WHERE tid='$tid' AND `uid`!='{$this->user->id}'");
		if(!$update){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=topics&op=view&tid=$link", "Внимание!", 3); }

		$update = $this->db->query("UPDATE `qx_mb_topics` SET `date_update`='$time' WHERE id='$tid'");
		if(!$update){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=topics&op=view&tid=$link", "Внимание!", 3); }
		

		$link = 'http://'.$_SERVER["SERVER_NAME"].BASE_URL.'?mode=mailbox&do=topics&op=view&tid='.$link;

		$gid = intval($ar[$bd_users['group']]);

		if($this->cfg['use_email'] && $gid!=2 && $gid!=4){

			$subject = '['.$config["s_name"].'] Оповещение о новых сообщениях';
			$message = '<p>На сайте '.$config["s_name"].' появились новые непрочитанные личные сообщения</p>';
			$message .= '<p>Для просмотра, вы можете перейти по ссылке ниже:</p>';
			$message .= '<p><a href="'.$link.'">'.$link.'</a></p>';
			$this->api->email_load($this->cfg['use_email_ssl']);

			$this->api->email($email, $subject, $message);
		}

		$this->api->notify("Ответ был успешно добавлен", "&do=topics&op=view&tid=$link", "Поздравляем!", 1);
	}

	private function topic_view(){
		if(!isset($_GET['tid']) || empty($_GET['tid'])){ $this->api->notify("Страница не найдена", "", "404", 3); }

		$api_security		= 'mb_msg_action_view';

		$link = intval($_GET['tid']);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$action = (isset($_POST['action'])) ? $_POST['action'] : '';

			switch($action){
				case 'reply': $this->reply_add(); break;

				default: $this->api->notify("Action is not set", "&do=topics&op=view&tid=$id", "403", 3); break;
			}
		}
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `l`.id, `l`.`read`, `l`.`creator`,
										`t`.id AS `tid`, `t`.`topic`, `t`.`text_html`, `t`.`date_create`, `t`.`date_update`, `t`.`closed`,
										`f`.`uniq`, `f`.title,
										`uf`.`{$bd_users['id']}` AS `uid_from`, `uf`.`{$bd_users['login']}` AS `login_from`,
										`gf`.`name` AS `group_from`,
										`g`.`name` AS `group`

									FROM `qx_mb_topics_links` AS `l`

									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.`id`=`l`.`tid`

									INNER JOIN `qx_mb_folders` AS `f`
										ON `f`.id=`l`.fid

									LEFT JOIN `qx_mb_topics_links` AS `lf`
										ON `lf`.`tid`=`t`.`id` AND `lf`.uid!=`l`.`uid`

									LEFT JOIN `{$bd_names['users']}` AS `uf`
										ON `uf`.`{$bd_users['id']}`=`lf`.`uid`

									LEFT JOIN `{$bd_names['groups']}` AS `gf`
										ON `gf`.id=`uf`.`{$bd_users['group']}`

									LEFT JOIN `{$bd_names['groups']}` AS `g`
										ON `g`.id='{$this->user->group}'

									WHERE `l`.`id`='$link' AND `l`.`uid`='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Страница не найдена", "", "404", 3); }

		$ar = $this->db->get_row($query);

		$tid = intval($ar['tid']);

		$topic = $this->db->HSC($ar['topic']);
		$title = $this->db->HSC($ar['title']);
		$creator = intval($ar['creator']);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			$title => MOD_URL.'&do=topics&op=folder&fid='.$this->db->HSC($ar['uniq']),
			$topic => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= $title.' — '.$topic; // Set title page

		$uniq = $this->db->HSC($ar['uniq']);

		if(intval($ar['read'])===0){
			$update = $this->db->query("UPDATE `qx_mb_topics_links` SET `read`='1' WHERE id='$link' AND uid='{$this->user->id}'");
			if(!$update){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=folders&op=$uniq", "Внимание!", 3); }
		}

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_mb_reply` WHERE tid='$tid'"; // Set SQL query for pagination function

		$page			= "&do=topics&op=view&tid=$tid&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_reply'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$uid_from		= ($creator===$this->user->id) ? $this->user->id : intval($ar['uid_from']);
		$login_from		= ($creator===$this->user->id) ? $this->user->login : $ar['login_from'];
		$group_from		= ($creator===$this->user->id) ? $ar['group'] : $ar['group_from'];

		$login_from		= $this->db->HSC($login_from);

		$data = array(
			'ID'			=> $link,
			'TID'			=> $tid,
			'TOPIC'			=> $topic,
			'TEXT'			=> $ar['text_html'],
			'DATE_CREATE'	=> date("d.m.Y в H:i:s", intval($ar['date_create'])),
			'DATE_UPDATE'	=> date("d.m.Y в H:i:s", intval($ar['date_update'])),
			'TITLE'			=> $title,
			'FROM_ID'		=> $uid_from,
			'FROM_LOGIN'	=> $login_from,
			'FROM_GROUP'	=> $this->db->HSC($group_from),
			'FROM_LOGIN_URL'=> ($this->cfg['use_us']) ? $this->url_us.$login_from : '#',
			'REPLY'			=> $this->reply_array($tid),
			'PAGINATION'	=> $pagination,
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"IS_DISABLED"	=> (intval($ar['closed'])===1) ? 'disabled' : '',
			"CLOSE_BTN"		=> (intval($ar['closed'])===1) ? 'Переписка закрыта' : 'Закрыть переписку',
		);

		$data['REPLY_FORM'] = (intval($ar['closed'])===1) ? $this->api->sp("topics/reply-closed.html") : $this->api->sp("topics/reply-form.html", $data);

		return $this->api->sp("topics/topic-full.html", $data);
	}

	private function topic_new(){
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];
		$config			= $this->mcfg['config'];

		$api_security		= 'mb_msg_action_new';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$is_send = (isset($_POST['send'])) ? true : false;

			$login = $this->db->safesql(trim(@$_POST['login']));
			$topic = $this->db->safesql(trim(@$_POST['topic']));
			$text = trim(@$_POST['text']);

			$text_bb	= $this->db->safesql($this->db->HSC($text));
			$text_html	= $this->api->bb_decode($text);
			$text_html	= $this->db->safesql(trim($text_html));

			if(empty($text_html)){ $this->api->notify("Не заполнен текст ответа", "&do=topics&op=view&tid=$id", "Ошибка!", 4); }

			if(empty($login) || empty($topic) || empty($text_html)){
				$this->api->notify("Не заполнены все поля!", "&do=topics&op=new", "Ошибка!", 3);
			}

			$query = $this->db->query("SELECT `{$bd_users['id']}`, `{$bd_users['email']}`, `{$bd_users['group']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$login'");

			if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Пользователь не найден!", "&do=topics&op=new", "Ошибка!", 3); }

			$ar = $this->db->get_row($query);

			$gid = intval($ar[$bd_users['group']]);
			$uid = intval($ar[$bd_users['id']]);
			$email = $this->db->HSC($ar[$bd_users['email']]);

			if($uid==$this->user->id && $is_send){ $this->api->notify("Вы не можете отправлять сообщения самому себе!", "&do=topics&op=new", "Ошибка!", 3); }

			$time = time();

			$insert = $this->db->query("INSERT INTO `qx_mb_topics`
											(uid, `topic`, text_bb, text_html, `date_create`, `date_update`)
										VALUES
											('{$this->user->id}', '$topic', '$text_bb', '$text_html', '$time', '$time')", 3);
			if(!$insert){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=topics&op=new", "Внимание!"); }

			$tid = $this->db->insert_id();

			$insert = $this->db->query("INSERT INTO `qx_mb_topics_links`
											(uid, `creator`, tid, fid, `read`)
										VALUES
											('$uid', '{$this->user->id}', '$tid', '1', '0'),
											('{$this->user->id}', '{$this->user->id}', '$tid', '2', '1')", 1);
			if(!$insert){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "&do=topics&op=new", "Внимание!"); }

			$lid = $this->db->insert_id();

			$link = 'http://'.$_SERVER["SERVER_NAME"].BASE_URL.'?mode=mailbox&do=topics&op=view&tid='.$lid;

			//$gid
			if($this->cfg['use_email'] && $gid!=2 && $gid!=4){
				$subject = '['.$config["s_name"].'] Оповещение о новых сообщениях';
				$message = '<p>На сайте '.$config["s_name"].' появились новые непрочитанные личные сообщения</p>';
				$message .= '<p>Для просмотра, вы можете перейти по ссылке ниже:</p>';
				$message .= '<p><a href="'.$link.'">'.$link.'</a></p>';
				$this->api->email_load($this->cfg['use_email_ssl']);

				$this->api->email($email, $subject, $message);
			}

			$this->api->notify("Переписка была успешно создана", "", "Поздравляем!", 1);
			//$this->api->notify("Переписка была успешно создана", "&do=topics&op=view&tid=$id", "Поздравляем!", 1);
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Создание переписки' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Создание переписки'; // Set title page

		$data = array(
			"TO"			=> $this->db->HSC(@$_GET['to']),
			"TOPIC"			=> $this->db->HSC(@$_GET['topic']),
			"MESSAGE"		=> $this->db->HSC(@$_GET['msg']),
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
		);

		return $this->api->sp("topics/topic-new.html", $data);
	}

	private function topic_array(){

		$uniq = $this->db->safesql($_GET['op']);

		$start		= $this->api->pagination($this->cfg['rop_topics'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_topics']; // Set end pagination

		$this->mcfg		= $this->api->getMcrConfig();
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `l`.`id`, `l`.`read`, `l`.`creator`,
											`t`.`id` AS `tid`, `t`.`topic`, `t`.`date_create`, `t`.`date_update`, `t`.`closed`,
											`uf`.`{$bd_users['login']}` AS `login_from`, `uf`.`{$bd_users['id']}` AS `uid_from`

									FROM `qx_mb_topics_links` AS `l`

									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.`id`=`l`.`tid`

									LEFT JOIN `qx_mb_topics_links` AS `lf`
										ON `lf`.`tid`=`t`.`id` AND `lf`.`uid`!=`l`.`uid`

									LEFT JOIN `{$bd_names['users']}` AS `uf`
										ON `uf`.`{$bd_users['id']}`=`lf`.`uid`

									WHERE `l`.`uid`='{$this->user->id}' AND `l`.`fid`='{$this->fid}'
									ORDER BY `l`.`read` DESC, `l`.`id` DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("topics/topic-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$creator = intval($ar['creator']);

			$uid_from		= ($creator===$this->user->id) ? $this->user->id : intval($ar['uid_from']);
			$login_from		= ($creator===$this->user->id) ? $this->user->login : $ar['login_from'];
			$uid_to			= ($creator!==$this->user->id) ? $this->user->id : intval($ar['uid_from']);
			$login_to		= ($creator!==$this->user->id) ? $this->user->login : $ar['login_from'];

			// Filter output
			$login_from		= $this->db->HSC($login_from);
			$login_to		= $this->db->HSC($login_to);

			// Check user system module
			$login_url_from	= ($this->cfg['use_us']) ? '<a href="'.$this->url_us.$login_from.'">'.$login_from.'</a>' : $login_from;
			$login_url_to	= ($this->cfg['use_us']) ? '<a href="'.$this->url_us.$login_to.'">'.$login_to.'</a>' : $login_to;
			
			$data = array(
				"ID"			=> intval($ar['id']),
				"TOPIC"			=> $this->db->HSC($ar['topic']),
				"IS_READ"		=> (intval(@$ar['read'])===0) ? 'info' : '',
				"DATE_CREATE"	=> date("d.m.Y в H:i:s", intval($ar['date_create'])),
				"DATE_UPDATE"	=> date("d.m.Y в H:i:s", intval($ar['date_update'])),
				"UID_FROM"		=> $uid_from,
				"UID_TO"		=> $uid_to,
				"LOGIN_URL_FROM"=> $login_url_from,
				"LOGIN_URL_TO"	=> $login_url_to,
				"LOGIN_FROM"	=> $login_from,
				"LOGIN_TO"		=> $login_to,
				"CLOSED"		=> (intval($ar['closed'])===1) ? '<i class="icon-lock" rel="tooltip" title="Переписка закрыта"></i>' : '',
			);

			echo $this->api->sp("topics/topic-id.html", $data);
		}

		return ob_get_clean();
	}

	private function topic_close(){ // POST

		$ids = $this->api->filter_array_integer(@$_POST['act']);

		$ids = implode(', ', $ids);

		$query = $this->db->query("SELECT `t`.`id`
									FROM `qx_mb_topics_links` AS `l`
									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.`id`=`l`.`tid` AND `t`.`closed`='0'
									WHERE `l`.tid IN ($ids) AND `l`.`uid`='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Доступ запрещен!", "403", 3); }

		$id_row = array();

		$time = time();

		while($ar = $this->db->get_row($query)){
			$id_row[] = intval($ar['id']);
		}

		$id_row = implode(', ', $id_row);

		$update = $this->db->query("UPDATE `qx_mb_topics` SET `date_update`='$time', `closed`='1', `closed_by`='{$this->user->id}' WHERE id IN ($id_row)");
		if(!$update){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "", "Внимание!", 3); }
		
		if($this->db->get_affected_rows()<=0){ $this->api->notify("Переписки не были обновлены", "", "Внимание!", 4); }
		
		$this->api->notify('Переписки успешно закрыты', "", "Поздравляем!", 1);
	}

	private function topic_remove(){ // POST

		$ids = $this->api->filter_array_integer($_POST['act']);

		$ids = implode(', ', $ids);

		$query = $this->db->query("SELECT `l`.id
									FROM `qx_mb_topics_links` AS `l`
									INNER JOIN `qx_mb_topics` AS `t`
										ON `t`.id=`l`.`tid`
									WHERE `l`.id IN ($ids) AND `l`.uid='{$this->user->id}' AND `l`.`fid`!='3'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Доступ запрещен!", "403", 3); }

		$id_row = array();

		while($ar = $this->db->get_row($query)){
			$id_row[] = intval($ar['id']);
		}

		$id_row = implode(', ', $id_row);

		$update = $this->db->query("UPDATE `qx_mb_topics_links` SET `fid`='3' WHERE id IN ($id_row)");
		if(!$update){ $this->api->notify("Произошла ошибка базы данных topics #".__LINE__, "", "Внимание!", 3); }
		
		if($this->db->get_affected_rows()<=0){ $this->api->notify("Переписки не были удалены", "", "Внимание!", 4); }
		
		$this->api->notify('Переписки успешно перемещены в корзину', "", "Поздравляем!", 1);
	}

	private function topic_list(){

		if(!isset($_GET['fid']) || empty($_GET['fid'])){ $this->api->notify("Папка не найдена", "&do=folders", "404", 3); }

		$uniq = $this->db->safesql(@$_GET['fid']);
		$h_uniq = $this->db->HSC(@$_GET['fid']);

		$query = $this->db->query("SELECT id, title FROM `qx_mb_folders` WHERE `uniq`='$uniq' AND (`uid`='{$this->user->id}' OR `system`='1')");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Папка не найдена", "&do=folders", "404", 3); }

		$ar = $this->db->get_row($query);

		$this->fid = intval($ar['id']);

		$api_security		= 'mb_msg_action';

		if($_SERVER['REQUEST_METHOD']=='POST'){

			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$action = (isset($_POST['action'])) ? $_POST['action'] : '';

			switch($action){
				case 'move': $this->topic_move(); break;
				case 'close': $this->topic_close(); break;
				case 'remove': $this->topic_remove(); break;

				default: $this->api->notify("Неверное действие!", "", "Ошибка!", 3); break;
			}
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			$this->db->HSC($ar['title']) => MOD_URL.'&do=topics&op=folder&fid='.$h_uniq,
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= $this->db->HSC($ar['title']); // Set title page

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_mb_topics_links` WHERE `uid`='{$this->user->id}' AND `fid`='{$this->fid}'"; // Set SQL query for pagination function

		$page			= "&do=folders&op=".$h_uniq."&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_topics'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->topic_array(),
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
		);

		return $this->api->sp('topics/topic-list.html', $data);
	}

	public function _list(){
		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'view':	return $this->topic_view(); break;
			case 'folder':	return $this->topic_list(); break;
			case 'new':		return $this->topic_new(); break;

			default: $this->api->notify("Страница не найдена", "", "404", 3); break;
		}
	}
}

/**
 * MailBox module for WebMCR
 *
 * Topics class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 3.0.0
 *
 */
?>
