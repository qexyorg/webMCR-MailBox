<?php
/**
 * User-System module for WebMCR
 *
 * Admin class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.2.0
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

	// Accepted formats
	private $formats		= array('jpg', 'png', 'jpeg', 'gif');

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= $this->api->getMcrConfig();

		if($this->user->lvl < $this->cfg['lvl_admin']){
			$this->api->notify("Доступ запрещен!", "", "403", 3);
		}
	}

	private function main_settings(){
		$api_security		= 'mb_settings';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }

			$this->cfg['title']			= $this->db->HSC(strip_tags(@$_POST['title']));
			$this->cfg['lvl_access']	= intval(@$_POST['lvl_access']);
			$this->cfg['lvl_admin']		= intval(@$_POST['lvl_admin']);
			$this->cfg['use_email']		= (intval(@$_POST['use_email'])===1) ? true : false;
			$this->cfg['use_us']		= (intval(@$_POST['use_us'])===1) ? true : false;
			$this->cfg['max_folders']	= (intval(@$_POST['max_folders'])<=0) ? 1 : intval(@$_POST['max_folders']);
			$this->cfg['rop_folders']	= (intval(@$_POST['rop_folders'])<=0) ? 1 : intval(@$_POST['rop_folders']);
			$this->cfg['rop_topics']	= (intval(@$_POST['rop_topics'])<=0) ? 1 : intval(@$_POST['rop_topics']);
			$this->cfg['rop_reply']		= (intval(@$_POST['rop_reply'])<=0) ? 1 : intval(@$_POST['rop_reply']);
			$this->cfg['rop_control']	= (intval(@$_POST['rop_control'])<=0) ? 1 : intval(@$_POST['rop_control']);
			$this->cfg['rop_icons']		= (intval(@$_POST['rop_icons'])<=0) ? 1 : intval(@$_POST['rop_icons']);

			if(!$this->api->savecfg($this->cfg, 'configs/mb.cfg.php')){
				$this->api->notify("Произошла ошибка сохранения настроек", "&do=admin", "Ошибка!", 3);
			}
			
				$this->api->notify("Настройки успешно сохранены", "&do=admin", "Поздравляем!", 1);

		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Настройки" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Настройки";

		$data = array(
			"USE_US"		=> ($this->cfg['use_us']===true) ? 'selected' : '',
			"USE_EMAIL"		=> ($this->cfg['use_email']===true) ? 'selected' : '',
			"USE_EMAIL_SSL"	=> ($this->cfg['use_email_ssl']===true) ? 'selected' : '',
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
		);

		return $this->api->sp('admin/main.html', $data);
	}

	private function clear(){
		$api_security		= 'mb_clear';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }

			$login = $this->db->safesql(trim(@$_POST['login']));

			if(empty($login)){ $this->api->notify("Необходимо заполнить поле логина пользователя!", "&do=admin&op=clear", "Ошибка!", 3); }
			
		
			$bd_names		= $this->mcfg['bd_names'];
			$bd_users		= $this->mcfg['bd_users'];

			$query = $this->db->query("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$login'");
			
			if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Пользователь не найден!", "&do=admin&op=clear", "Ошибка!", 3); }

			$ar = $this->db->get_row($query);

			$id = intval($ar[$bd_users['id']]);

			$delete1 = $this->db->query("DELETE FROM `qx_mb_topics_links` WHERE `creator`='$id'");

			if(!$delete1){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=clear", "Внимание!"); }

			$delete2 = $this->db->query("DELETE FROM `qx_mb_topics` WHERE `uid`='$id'");

			if(!$delete2){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=clear", "Внимание!"); }

			$delete3 = $this->db->query("DELETE FROM `qx_mb_reply` WHERE `uid`='$id'");

			if(!$delete3){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=clear", "Внимание!"); }

			$this->api->notify("Все сообщения пользователя ".$this->db->HSC($login)." успешно удалены!", "&do=admin&op=clear", "Поздравляем!", 1);

		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Удаление сообщений" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Удаление сообщений";

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
		);

		return $this->api->sp('admin/clear.html', $data);
	}

	private function icons_array(){
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$start		= $this->api->pagination($this->cfg['rop_icons'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_icons']; // Set end pagination

		$query = $this->db->query("SELECT id, img, `system`
									FROM `qx_mb_icons`
									ORDER BY id ASC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/icon-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$data = array(
				"ID"			=> intval($ar['id']),
				"IMG"			=> $this->db->HSC($ar['img']),
				"SYSTEM"		=> (intval($ar['system'])) ? 'Да' : 'Нет',
			);

			echo $this->api->sp("admin/icon-id.html", $data);
		}

		return ob_get_clean();
	}

	private function icons_remove(){ // POST
		$ids = $this->api->filter_array_integer(@$_POST['act']);

		$check = array_intersect($ids, array(1,2,3,4));

		if(!empty($check)){
			$this->api->notify("Вы не можете удалять системные иконки", "&do=admin&op=icons", "Ошибка!", 3);
		}

		$ids = implode(', ', $ids);

		$query = $this->db->query("SELECT img, `ext` FROM `qx_mb_icons` WHERE id IN ($ids) AND `system`='0'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Не выбраны иконки для удаления", "&do=admin&op=icons", "Внимание!", 3); }

		while($ar = $this->db->get_row($query)){
			@unlink(MCR_ROOT.'qx_upload/mailbox/'.$ar['img'].$ar['ext']);
			@unlink(MCR_ROOT.'qx_upload/mailbox/'.$ar['img'].'-exist'.$ar['ext']);
		}

		$update = $this->db->query("UPDATE `qx_mb_folders` SET icon='4' WHERE icon IN ($ids)");
		
		if(!$update){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=icons", "Внимание!", 3); }

		$delete = $this->db->query("DELETE FROM `qx_mb_icons` WHERE id IN ($ids) AND `system`='0'");
		
		if(!$delete){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=icons", "Внимание!", 3); }
		
		$this->api->notify("Выбранные иконки были удалены", "&do=admin&op=icons", "Поздравляем!", 1);
	
	}

	private function icons_edit(){

		$id = intval(@$_GET['iid']);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Панель управления' => MOD_URL.'&do=admin',
			'Управление иконками' => MOD_URL.'&do=admin&op=icons',
			'Редактирование' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Панель управления — Управление иконками — Редактирование'; // Set title page

		$api_security		= 'mb_icons_edit';

		$query = $this->db->query("SELECT img, `ext` FROM `qx_mb_icons` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Иконка не найдена", "&do=admin&op=icons", "Ошибка!", 3); }

		$ar = $this->db->get_row($query);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			if(empty($_FILES['empty']['size']) || empty($_FILES['exist']['size'])){ $this->api->notify("Необходимо заполнить все поля", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); }
			
			switch($_FILES['empty']['error'] || $_FILES['exist']['error']){
				case 0: break;
				case 1:
				case 2: $this->api->notify("Максимально допустимый размер файла 2 MB", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); break;
				case 3:
				case 4: $this->api->notify("Ошибка загрузки файла", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); break;
				case 6: $this->api->notify("Отсутствует временная папка", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); break;
				case 7: $this->api->notify("Отсутствуют права на запись", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); break;
				default: $this->api->notify("Неизвестная ошибка", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); break;
			}

			if(!file_exists($_FILES['empty']['tmp_name']) || !file_exists($_FILES['exist']['tmp_name'])){
				$this->api->notify("Временный файл не существует", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3);
			}

			$name_empty = mb_strtolower($_FILES['empty']['name'], 'UTF-8');
			$name_exist = mb_strtolower($_FILES['exist']['name'], 'UTF-8');

			$ext_empty = substr(strrchr($name_empty, '.'), 1);
			$ext_exist = substr(strrchr($name_exist, '.'), 1);

			$gis_empty = @getimagesize($_FILES['empty']['tmp_name']);
			$gis_exist = @getimagesize($_FILES['exist']['tmp_name']);

			if($ext_empty != $ext_exist){
				$this->api->notify("Форматы изображений должны быть одинаковыми", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3);
			}

			if(!in_array($ext_empty, $this->formats) || !in_array($ext_exist, $this->formats)){
				$this->api->notify("Разрешено загружать только форматы: ".$this->db->HSC(implode(', ', $this->formats)), "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3);
			}

			if(!$gis_empty || !$gis_exist){ $this->api->notify("Неверный формат изображения", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3); }

			$new_name = $this->db->safesql($this->api->gen(24));
			$ext = $this->db->safesql($ext_exist);

			if(!move_uploaded_file($_FILES['empty']['tmp_name'], MCR_ROOT.'qx_upload/mailbox/'.$new_name.'.'.$ext) ||
				!move_uploaded_file($_FILES['exist']['tmp_name'], MCR_ROOT.'qx_upload/mailbox/'.$new_name.'-exist.'.$ext)){
				$this->api->notify("Не удалось загрузить файлы на сервер", "&do=admin&op=icons_edit&iid=$id", "Ошибка!", 3);
			}

			$update = $this->db->query("UPDATE `qx_mb_icons`
										SET img='$new_name', `ext`='$ext'
										WHERE id='$id'");

			if(!$update){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=icons_edit&iid=$id", "Внимание!", 3); }

			$this->api->notify("Иконка успешно обновлена", "&do=admin&op=icons_edit&iid=$id", "Поздравляем!", 1);
		}

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"BTN"			=> 'Сохранить',
			"IMG_EMPTY"		=> '<img src="'.BASE_URL.'qx_upload/mailbox/'.$this->db->HSC($ar['img']).$this->db->HSC($ar['ext']).'" width="64px" height="64px" alt="" />',
			"IMG_EXIST"		=> '<img src="'.BASE_URL.'qx_upload/mailbox/'.$this->db->HSC($ar['img']).'-exist'.$this->db->HSC($ar['ext']).'" width="64px" height="64px" alt="" />',
			"FORMATS"		=> $this->db->HSC(implode(', ', $this->formats)),
		);

		return $this->api->sp('admin/icon-add.html', $data);
	}

	private function icons_add(){

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Панель управления' => MOD_URL.'&do=admin',
			'Управление иконками' => MOD_URL.'&do=admin&op=icons',
			'Добавление' => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Панель управления — Управление иконками — Добавление'; // Set title page

		$api_security		= 'mb_icons_add';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			if(empty($_FILES['empty']['size']) || empty($_FILES['exist']['size'])){ $this->api->notify("Необходимо заполнить все поля", "&do=admin&op=icons_add", "Ошибка!", 3); }
			
			switch($_FILES['empty']['error'] || $_FILES['exist']['error']){
				case 0: break;
				case 1:
				case 2: $this->api->notify("Максимально допустимый размер файла 2 MB", "&do=admin&op=icons_add", "Ошибка!", 3); break;
				case 3:
				case 4: $this->api->notify("Ошибка загрузки файла", "&do=admin&op=icons_add", "Ошибка!", 3); break;
				case 6: $this->api->notify("Отсутствует временная папка", "&do=admin&op=icons_add", "Ошибка!", 3); break;
				case 7: $this->api->notify("Отсутствуют права на запись", "&do=admin&op=icons_add", "Ошибка!", 3); break;
				default: $this->api->notify("Неизвестная ошибка", "&do=admin&op=icons_add", "Ошибка!", 3); break;
			}

			if(!file_exists($_FILES['empty']['tmp_name']) || !file_exists($_FILES['exist']['tmp_name'])){
				$this->api->notify("Временный файл не существует", "&do=admin&op=icons_add", "Ошибка!", 3);
			}

			$name_empty = mb_strtolower($_FILES['empty']['name'], 'UTF-8');
			$name_exist = mb_strtolower($_FILES['exist']['name'], 'UTF-8');

			$ext_empty = substr(strrchr($name_empty, '.'), 1);
			$ext_exist = substr(strrchr($name_exist, '.'), 1);

			$gis_empty = @getimagesize($_FILES['empty']['tmp_name']);
			$gis_exist = @getimagesize($_FILES['exist']['tmp_name']);

			if($ext_empty != $ext_exist){
				$this->api->notify("Форматы изображений должны быть одинаковыми", "&do=admin&op=icons_add", "Ошибка!", 3);
			}

			if(!in_array($ext_empty, $this->formats) || !in_array($ext_exist, $this->formats)){
				$this->api->notify("Разрешено загружать только форматы: ".$this->db->HSC(implode(', ', $this->formats)), "&do=admin&op=icons_add", "Ошибка!", 3);
			}

			if(!$gis_empty || !$gis_exist){ $this->api->notify("Неверный формат изображения", "&do=admin&op=icons_add", "Ошибка!", 3); }

			$new_name = $this->db->safesql($this->api->gen(24));
			$ext = $this->db->safesql($ext_exist);

			if(!move_uploaded_file($_FILES['empty']['tmp_name'], MCR_ROOT.'qx_upload/mailbox/'.$new_name.'.'.$ext) ||
				!move_uploaded_file($_FILES['exist']['tmp_name'], MCR_ROOT.'qx_upload/mailbox/'.$new_name.'-exist.'.$ext)){
				$this->api->notify("Не удалось загрузить файлы на сервер", "&do=admin&op=icons_add", "Ошибка!", 3);
			}

			$insert = $this->db->query("INSERT INTO `qx_mb_icons`
											(img, `ext`)
										VALUES
											('$new_name', '.$ext')");

			if(!$insert){ $this->api->notify("Произошла ошибка базы данных admin #".__LINE__, "&do=admin&op=icons_add", "Внимание!", 3); }

			$this->api->notify("Иконка успешно добавлена", "&do=admin&op=icons", "Поздравляем!", 1);
		}

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"BTN"			=> 'Добавить',
			"IMG_EMPTY"		=> '',
			"IMG_EXIST"		=> '',
			"FORMATS"		=> $this->db->HSC(implode(', ', $this->formats)),
		);

		return $this->api->sp('admin/icon-add.html', $data);
	}

	private function icons(){

		$api_security		= 'mb_icons';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }

			$action = (isset($_POST['action'])) ? $_POST['action'] : '';

			switch($action){
				case 'remove': $this->icons_remove(); break;

				default: $this->api->notify("Action is not set", "&do=admin&op=icons", "403", 3); break;
			}
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			'Панель управления' => MOD_URL.'&do=admin',
			'Управление иконками' => MOD_URL.'&do=admin&op=icons',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Панель управления — Управление иконками'; // Set title page
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_mb_icons`"; // Set SQL query for pagination function

		$page			= "&do=admin&op=icons&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_icons'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$data = array(
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->icons_array(),
		);

		return $this->api->sp('admin/icon-list.html', $data);
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'icons_add': return $this->icons_add(); break;
			case 'icons_edit': return $this->icons_edit(); break;
			case 'icons': return $this->icons(); break;
			case 'clear': return $this->clear(); break;

			default: return $this->main_settings(); break;
		}
	}

}

/**
 * User-System module for WebMCR
 *
 * Admin class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.2.0
 *
 */
?>