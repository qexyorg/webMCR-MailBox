<?php
/**
 * MailBox module for WebMCR
 *
 * Folders class
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

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		//$this->mcfg			= $this->api->getMcrConfig();

	}

	private function folder_array(){

		$start		= $this->api->pagination($this->cfg['rop_folders'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_folders']; // Set end pagination

		$query = $this->db->query("SELECT `f`.`id`, `f`.`uniq`, `f`.`title`,
		`i`.`img`, `i`.`ext`,
		COUNT(DISTINCT `lr`.`id`) AS `num_read`,
		COUNT(DISTINCT `lu`.`id`) AS `num_unread`
FROM `qx_mb_folders` AS `f`

LEFT JOIN `qx_mb_icons` AS `i`
	ON `i`.`id`=`f`.`icon`

LEFT JOIN `qx_mb_topics_links` AS `lu`
	ON `lu`.`fid`=`f`.`id` AND `lu`.uid='{$this->user->id}' AND `lu`.`read`='0'

LEFT JOIN `qx_mb_topics_links` AS `lr`
	ON `lr`.`fid`=`f`.`id` AND `lr`.uid='{$this->user->id}' AND `lr`.`read`='1'

WHERE `f`.`uid`='{$this->user->id}' OR `f`.`system`='1'
GROUP BY `f`.`id`, `lu`.`fid`, `lr`.`fid`
ORDER BY `f`.`id` ASC
LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("folders/folder-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$uniq			= $this->db->HSC($ar['uniq']);
			$num_read		= intval($ar['num_read']);
			$num_unread		= intval($ar['num_unread']);
			$num_over		= $num_unread+$num_read;
			$img			= $this->db->HSC($ar['img']);
			$ext			= $this->db->HSC($ar['ext']);
			$img_url		= BASE_URL.'qx_upload/mailbox/'.$img.$ext;

			if($num_over!=0){ $img_url = BASE_URL.'qx_upload/mailbox/'.$img.'-exist'.$ext; }

			if($num_over==0){
				$popup = "Папка пуста";
			}elseif($num_over==$num_read){
				$popup = "Нет непрочитанных сообщений";
			}else{
				$popup = "Есть непрочитанные сообщения";
			}
			
			$data = array(
				"ID"			=> intval($ar['id']),
				"URL"			=> MOD_URL.'&do=topics&op=folder&fid='.$this->db->HSC($ar['uniq']),
				"TITLE"			=> $this->db->HSC($ar['title']),
				"IMG_URL"		=> $img_url,
				"NUM_UNREAD"	=> $num_unread,
				"NUM_OVER"		=> $num_over,
				"POPUP"			=> $popup
			);
			
			$data['UNREAD'] = ($num_over!=$num_read) ? $this->api->sp("folders/folder-unread.html", $data) : "";

			echo $this->api->sp("folders/folder-id.html", $data);
		}

		return ob_get_clean();
	}

	private function folder_list(){

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Список папок' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Главная'; // Set title page

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_mb_folders` WHERE `uid`='{$this->user->id}' OR `system`='1'"; // Set SQL query for pagination function

		$page			= "&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_folders'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->folder_array(),
			"MENU"			=> ($this->user->lvl<$this->cfg['lvl_admin']) ? '' : $this->api->sp("admin/menu.html"),
		);

		return $this->api->sp('folders/folder-list.html', $data);
	}

	private function folder_control_array(){

		$start		= $this->api->pagination($this->cfg['rop_control'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_control']; // Set end pagination

		$query = $this->db->query("SELECT id, `uniq`, title, `system`, `data`
									FROM `qx_mb_folders`
									WHERE uid='{$this->user->id}' OR `system`='1'
									ORDER BY id ASC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("control/folder-none.html"); }
		
		$id = (isset($_GET['pid'])) ? 1*intval($_GET['pid']) : 1;

		ob_start();

		while($ar = $this->db->get_row($query)){

			$json = json_decode($ar['data']);

			$data = array(
				'ID' => $id++,
				'FID' => intval($ar['id']),
				'UNIQ' => $this->db->HSC($ar['uniq']),
				'TITLE' => $this->db->HSC($ar['title']),
				'SYSTEM' => (intval($ar['system'])===1) ? 'Да <i class="icon-exclamation-sign" rel="tooltip" title="Нельзя редактировать и удалять"></i>' : 'Нет',
				'DATE_CREATE' => date("d.m.Y в H:i", $json->date_create),
				'DATE_UPDATE' => date("d.m.Y в H:i", $json->date_update),
			);

			echo $this->api->sp("control/folder-id.html", $data);
		}

		return ob_get_clean();
	}

	private function folder_remove(){ // POST

		$ids = $this->api->filter_array_integer(@$_POST['act']);
		$check = array_intersect($ids, array(1,2,3,4));

		if(!empty($check)){
			$this->api->notify("Вы не можете удалять системные папки", "&do=folders&op=control", "Ошибка!", 3);
		}

		$ids = implode(', ', $ids);

		$update = $this->db->query("UPDATE `qx_mb_topics_links` SET fid='4' WHERE id IN ($ids) AND uid='{$this->user->id}'");
		
		if(!$update){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=control", "Внимание!", 3); }

		$delete = $this->db->query("DELETE FROM `qx_mb_folders` WHERE id IN ($ids) AND uid='{$this->user->id}' AND `system`!='1'");
		
		if(!$delete){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=control", "Внимание!", 3); }
		
		$this->api->notify("Выбранные папки были удалены", "&do=folders&op=control", "Поздравляем!", 1);
	}

	private function folder_control(){

		$api_security		= 'mb_control';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$action = (isset($_POST['action'])) ? $_POST['action'] : '';

			switch($action){
				case 'remove': $this->folder_remove(); break;

				default: $this->api->notify("Action is not set", "&do=folders&op=control", "403", 3); break;
			}
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Управление папками' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Управление папками'; // Set title page

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_mb_folders` WHERE `uid`='{$this->user->id}' OR `system`='1'"; // Set SQL query for pagination function

		$page			= "&do=folders&op=control&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_control'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->folder_control_array(),
		);

		return $this->api->sp('control/folder-list.html', $data);
	}

	private function get_icons($checked=5){
		$checked = intval($checked);

		$query = $this->db->query("SELECT id, img FROM `qx_mb_icons` ORDER BY id ASC");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			$data = array(
				'ID' => intval($ar['id']),
				'IMG' => $this->db->HSC($ar['img']),
				'CHECKED' => (intval($ar['id'])==$checked) ? 'checked' : '',
			);

			echo $this->api->sp('control/folder-icon.html', $data);
		}

		return ob_get_clean();
	}

	private function folder_add(){

		$api_security		= 'mb_control_add';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }
		
			$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_folders` WHERE uid='{$this->user->id}' AND system='0'");
			
			if(!$query){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }
		
			$ar = $this->db->get_array($query);

			if($ar[0]>=$this->cfg['max_folders']){ $this->api->notify("Папка не создана. Достигнут лимит на создание новых папок", "&do=folders&op=add_folder", "Ошибка!", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));
			$uniq = $this->db->safesql(trim(@$_POST['uniq']));
			$icon = intval(@$_POST['icon']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=folders&op=add_folder", "Ошибка!", 4); }
			if(empty($uniq)){ $this->api->notify("Не заполнено поле \"Идентификатор\"", "&do=folders&op=add_folder", "Ошибка!", 4); }

			if(!preg_match("/^[a-z0-9_]+$/i", $uniq)){ $this->api->notify("В качестве идентификатора можно использовать только латиницу", "&do=folders&op=add_folder", "Ошибка!", 4); }

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_icons` WHERE id='$icon'");

			if(!$query){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }
		
			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $this->api->notify("Выбранная иконка не существует", "&do=folders&op=add_folder", "Ошибка!", 3); }
		
			$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_folders` WHERE `uniq`='$uniq' AND (uid='{$this->user->id}' OR system='1')");
			
			if(!$query){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }
		
			$ar = $this->db->get_array($query);

			if($ar[0]>0){ $this->api->notify("Идентификатор уже используется", "&do=folders&op=add_folder", "Внимание!", 3); }
			
			$new_data = array(
				'date_create' => time(),
				'date_update' => time()
			);

			$new_data = $this->db->safesql(json_encode($new_data));

			$insert = $this->db->query("INSERT INTO `qx_mb_folders`
											(`uniq`, title, uid, icon, `data`)
										VALUES
											('$uniq', '$title', '{$this->user->id}', '$icon', '$new_data')");

			if(!$insert){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }

			$this->api->notify("Папка успешно добавлена", "&do=folders&op=add_folder", "Поздравляем!", 1);
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Добавление папки' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Добавление папки'; // Set title page

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"TITLE"			=> "Новая папка",
			"UNIQ"			=> "",
			'ICONS' => $this->get_icons(),
			'BTN' => 'Добавить',
		);

		return $this->api->sp('control/folder-add.html', $data);
	}

	private function folder_edit(){

		$api_security		= 'mb_control_edit';

		$id = intval(@$_GET['fid']);

		$query = $this->db->query("SELECT `uniq`, title, icon, `data` FROM `qx_mb_folders` WHERE id='$id' AND uid='{$this->user->id}' AND `system`='0'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Доступ запрещен!", "&do=folders&op=control", "403", 3); }

		$ar = $this->db->get_row($query);

		$data = json_decode($ar['data'], true);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));
			$uniq = $this->db->safesql(trim(@$_POST['uniq']));
			$icon = intval(@$_POST['icon']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=folders&op=add_folder", "Ошибка!", 4); }
			if(empty($uniq)){ $this->api->notify("Не заполнено поле \"Идентификатор\"", "&do=folders&op=add_folder", "Ошибка!", 4); }

			if(!preg_match("/^[a-z0-9_]+$/i", $uniq)){ $this->api->notify("В качестве идентификатора можно использовать только латиницу", "&do=folders&op=add_folder", "Ошибка!", 4); }

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_icons` WHERE id='$icon'");

			if(!$query){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }
		
			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $this->api->notify("Выбранная иконка не существует", "&do=folders&op=add_folder", "Ошибка!", 3); }
		
			$query = $this->db->query("SELECT COUNT(*) FROM `qx_mb_folders` WHERE id!='$id' AND `uniq`='$uniq' AND (uid='{$this->user->id}' OR system='1')");
			
			if(!$query){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }
		
			$ar = $this->db->get_array($query);

			if($ar[0]>0){ $this->api->notify("Идентификатор уже используется", "&do=folders&op=add_folder", "Внимание!", 3); }
			
			$data['date_update'] = time();

			$new_data = $this->db->safesql(json_encode($data));

			$update = $this->db->query("UPDATE `qx_mb_folders`
										SET `uniq`='$uniq', title='$title', icon='$icon', `data`='$new_data'
										WHERE id='$id' AND `system`='0' AND uid='{$this->user->id}'");

			if(!$update){ $this->api->notify("Произошла ошибка базы данных folders #".__LINE__, "&do=folders&op=add_folder", "Внимание!", 3); }

			$this->api->notify("Папка успешно обновлена", "&do=folders&op=control", "Поздравляем!", 1);
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Редактирование папки '.$this->db->HSC($ar['title']) => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Редактирование папки '.$this->db->HSC($ar['title']); // Set title page

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"TITLE"			=> $this->db->HSC($ar['title']),
			"UNIQ"			=> $this->db->HSC($ar['uniq']),
			'ICONS'			=> $this->get_icons($ar['icon']),
			'BTN'			=> 'Добавить',
		);

		return $this->api->sp('control/folder-add.html', $data);
	}

	public function _list(){
		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){

			case 'control': return $this->folder_control(); break;
			case 'add_folder': return $this->folder_add(); break;
			case 'edit_folder': return $this->folder_edit(); break;

			default: return $this->folder_list(); break;
		}
	}
}

/**
 * MailBox module for WebMCR
 *
 * Folders class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 3.0.0
 *
 */
?>