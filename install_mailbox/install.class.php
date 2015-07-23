<?php
/**
 * MailBox module for WebMCR
 *
 * Install class
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

$content_js .= '<link href="'.BASE_URL.'install_mailbox/styles/css/install.css" rel="stylesheet">';

class module{
	// Set default variables
	private $cfg			= array();
	private $user			= false;
	private $db				= false;
	private $api			= false;
	private $configs		= array();
	public	$in_header		= '';
	public	$title			= '';

	// Set counstructor values
	public function __construct($api){

		$this->cfg			= $api->cfg;
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->api			= $api;
		
		if($this->user->lvl < $this->cfg['lvl_admin']){ $this->api->url = ''; $this->api->notify(); }
	}

	private function step_1(){

		if(!$this->cfg['install']){ $this->api->notify("Установка уже произведена", "", "Ошибка!", 3); }
		if(isset($_SESSION['step_2'])){ $this->api->notify("", "&do=install&op=2", "", 3); }

		$write_menu = $write_cfg = $write_configs = '';

		if(!is_writable(MCR_ROOT.'instruments/menu_items.php')){
			$write_menu = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на файл <b>instruments/menu_items.php</b></div>';
		}

		if(!is_writable(MCR_ROOT.'configs')){
			$write_configs = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на папку <b>configs</b></div>';
		}

		if(!is_writable(MCR_ROOT.'configs/mb.cfg.php')){
			$write_cfg = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на файл <b>configs/mb.cfg.php</b></div>';
		}

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install", "403", 3); }

			if(!empty($write_menu) || !empty($write_cfg) || !empty($write_configs)){ $this->api->notify("Требуется выставить необходимые права на запись", "&do=install", "Ошибка!", 3); }

			$this->cfg['title']			= $this->db->HSC(strip_tags(@$_POST['title']));
			$this->cfg['use_us']		= (intval(@$_POST['use_us'])===1) ? true : false;
			$this->cfg['use_email']		= (intval(@$_POST['use_email'])===1) ? true : false;
			$this->cfg['use_email_ssl']	= (intval(@$_POST['use_email_ssl'])===1) ? true : false;
			$this->cfg['max_folders']	= (intval(@$_POST['max_folders'])<=0) ? 1 : intval(@$_POST['max_folders']);

			// Check save config
			if(!$this->api->savecfg($this->cfg, "configs/mb.cfg.php")){ $this->api->notify("Ошибка сохранения настроек", "&do=install", "Ошибка!", 3); }

			$create1 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_mb_folders` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uniq` varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT 'main',
  `title` varchar(32) NOT NULL DEFAULT 'Новая папка',
  `uid` int(10) NOT NULL DEFAULT '1',
  `icon` int(10) NOT NULL DEFAULT '5',
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `data` text CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

			if(!$create1){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create2 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_mb_icons` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `img` varchar(255) NOT NULL DEFAULT 'folder',
  `ext` varchar(5) NOT NULL DEFAULT '.png',
  `system` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

			if(!$create2){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create3 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_mb_reply` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `tid` int(10) NOT NULL DEFAULT '1',
  `uid` int(10) NOT NULL DEFAULT '1',
  `text_bb` text NOT NULL,
  `text_html` text NOT NULL,
  `data` text CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

			if(!$create3){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create4 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_mb_topics` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL DEFAULT '1',
  `topic` varchar(128) NOT NULL,
  `text_bb` longtext NOT NULL,
  `text_html` longtext NOT NULL,
  `date_create` int(10) NOT NULL DEFAULT '1',
  `date_update` int(10) NOT NULL DEFAULT '1',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `closed_by` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

			if(!$create4){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create5 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_mb_topics_links` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `creator` int(10) NOT NULL DEFAULT '1',
  `tid` int(10) NOT NULL DEFAULT '0',
  `fid` int(10) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

			if(!$create5){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create6 = $this->db->query("INSERT INTO `qx_mb_folders` (`id`, `uniq`, `title`, `uid`, `icon`, `system`, `data`) VALUES
(1, 'inbox', 'Входящие', 0, 1, 1, '{\"date_create\":1,\"date_update\":1}'),
(2, 'outbox', 'Исходящие', 0, 2, 1, '{\"date_create\":1,\"date_update\":1}'),
(3, 'trash', 'Корзина', 0, 4, 1, '{\"date_create\":1,\"date_update\":1}');");

			if(!$create6){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create7 = $this->db->query("INSERT INTO `qx_mb_icons` (`id`, `img`, `ext`, `system`) VALUES
(1, 'inbox', '.png', 1),
(2, 'outbox', '.png', 1),
(3, 'trash', '.png', 1),
(4, 'folder', '.png', 1);");

			if(!$create7){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$_SESSION['step_2'] = true;

			$this->api->notify("Шаг 2", "&do=install&op=2", "Продолжение установки", 2);
		}

		$content = array(
			"WRITE_MENU" => $write_menu,
			"WRITE_CFG" => $write_cfg,
			"WRITE_CONFIGS" => $write_configs,
		);

		return $this->api->sp(MCR_ROOT.'install_mailbox/styles/step-1.html', $content, true);
	}

	private function saveMenu($menu) {
	
		$txt	= "<?php if (!defined('MCR')) exit;".PHP_EOL;
		$txt .= '$menu_items = '.var_export($menu, true).';'.PHP_EOL;

		$result = file_put_contents(MCR_ROOT."instruments/menu_items.php", $txt);

		return (is_bool($result) and $result == false)? false : true;
	}

	private function step_2(){

		if(!isset($_SESSION['step_2'])){ $this->api->notify("", "&do=install", "", 3); }
		if(isset($_SESSION['step_3'])){ $this->api->notify("", "&do=install&op=3", "", 3); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install&op=2", "403", 3); }

			require(MCR_ROOT."instruments/menu_items.php");

			if(!isset($menu_items[0]['qexy_mb'])){
				$menu_items[0]['qexy_mb'] = array (
					'name' => '<i class="icon-envelope"></i>',
					'url' => '?mode=mailbox',
					'parent_id' => -1,
					'lvl' => 1,
					'permission' => -1,
					'active' => false,
					'inner_html' => '',
				);

				$menu_items[0]['qexy_mb_new'] = array (
					'name' => 'Написать',
					'url' => '?mode=mailbox&do=topics&op=new',
					'parent_id' => 'qexy_mb',
					'lvl' => 1,
					'permission' => -1,
					'active' => false,
					'inner_html' => '',
				);

				$menu_items[0]['qexy_mb_inbox'] = array (
					'name' => 'Входящие',
					'url' => '?mode=mailbox&do=topics&op=folder&fid=inbox',
					'parent_id' => 'qexy_mb',
					'lvl' => 1,
					'permission' => -1,
					'active' => false,
					'inner_html' => '',
				);

				$menu_items[0]['qexy_mb_outbox'] = array (
					'name' => 'Исходящие',
					'url' => '?mode=mailbox&do=topics&op=folder&fid=outbox',
					'parent_id' => 'qexy_mb',
					'lvl' => 1,
					'permission' => -1,
					'active' => false,
					'inner_html' => '',
				);

				$menu_items[0]['qexy_mb_trash'] = array (
					'name' => 'Корзина',
					'url' => '?mode=mailbox&do=topics&op=folder&fid=trash',
					'parent_id' => 'qexy_mb',
					'lvl' => 1,
					'permission' => -1,
					'active' => false,
					'inner_html' => '',
				);
			}

			if(!$this->saveMenu($menu_items)){ $this->api->notify("Ошибка установки", "&do=install&op=2", "Ошибка!", 3); }

			$_SESSION['step_3'] = true;

			$this->api->notify("", "&do=install&op=3", "", 2);
		}

		return $this->api->sp(MCR_ROOT.'install_mailbox/styles/step-2.html', array(), true);
	}

	private function step_3(){

		if(!isset($_SESSION['step_3'])){ $this->api->notify("", "&do=install&op=2", "", 3); }
		if(isset($_SESSION['step_finish'])){ $this->api->notify("", "&do=install&op=finish", "", 3); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install", "403", 3); }

			$this->cfg['install'] = false;

			if(!$this->api->savecfg($this->cfg, "configs/mb.cfg.php")){ $this->api->notify("Ошибка установки", "&do=install", "Ошибка!", 3); }

			$_SESSION['step_finish'] = true;

			$this->api->notify("", "&do=install&op=finish", "", 2);
		}

		return $this->api->sp(MCR_ROOT.'install_mailbox/styles/step-3.html', array(), true);
	}

	private function finish(){

		if(!isset($_SESSION['step_finish'])){ $this->api->notify("", "&do=install&op=3", "", 3); }

		$content = $this->api->sp(MCR_ROOT.'install_mailbox/styles/finish.html', array(), true);

		unset($_SESSION['step_finish'], $_SESSION['step_3'], $_SESSION['step_2']);

		return $content;
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'main';

		switch($op){
			case "2":
				$this->title	= "Установка — Шаг 2"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 2" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->step_2(); // Set content
			break;

			case "3":
				$this->title	= "Установка — Шаг 3"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 3" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->step_3(); // Set content
			break;

			case "finish":
				$this->title	= "Установка — Конец установки"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Конец установки" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->finish(); // Set content
			break;

			default:
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 1" => ""
				);
				$this->bc		= $this->api->bc($array);

				$this->title	= "Установка — Шаг 1";
				return $this->step_1();
			break;
		}

		return '';
	}
}

/**
 * MailBox module for WebMCR
 *
 * Install class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 3.0.0
 *
 */
?>
