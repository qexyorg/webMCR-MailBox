<?php
/*
----------------------------------------
---- MailBox for webmcr by Qexy.org ----
---- Version: 2.0 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/
class MailBox
{
	private $num_folders = 0;
	private $num_messages = 0;

	private static function path($path){
		global $page;
		ob_start();

		include(MB_STYLE.'path.html');
		return ob_get_clean();
	}

	private static function is_full($folder, $pid, $num=0){
		$folder	= intval($folder);
		$pid	= intval($pid);
		$check	= MB_QUERY("SELECT COUNT(*) FROM `mb_messages` WHERE uid='$pid' AND folder='$folder'");
		if(!$check){ self::setINFO('Error! Debug - <b>S-1</b>', '', 3); }
		$ar		= MFA($check);
		$num	= $ar[0]+$num;
		if($num>MB_MESSAGES){ return false; }
		return true;
	}

	public function INFO(){
		ob_start();
		switch($_SESSION['mb_info_t']){
			case 1: $type = 'alert-success'; break;
			case 2: $type = 'alert-info'; break;
			case 3: $type = 'alert-error'; break;

			default: $type = ''; break;
		}

		include_once(MB_STYLE.'info.html');
		return ob_get_clean();
	}

	public function is_install(){
		global $mbcfg;
		if($mbcfg['install']===1){ return true; }
		return false;
	}

	public function install(){
		global $mbcfg, $config;
		ob_start();
		if(MB_LVL<15){header("Location: ".BASE_URL); exit;}
		if(!$this->is_install()){ include_once(MCR_ROOT.'install_mailbox/install-good.html'); return ob_get_clean(); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['install'])){
				include_once(MCR_ROOT.'install_mailbox/sql.php');
				if(!$sql1 || !$sql2){ self::setINFO('Ошибка установки', 'install/', 3); }
				$mbcfg['install'] = 0;
				if(!self::savecfg()){ self::setINFO('Ошибка установки 2', 'install/', 3); }
				self::setINFO('Установка успешно завершена!', 'install/', 1);
			}
		}
		
		include_once(MCR_ROOT.'install_mailbox/install.html');

		return ob_get_clean();
	}

	private function progress($max_f, $num_f){
		ob_start();
		$status = ceil(100 / $max_f * $num_f);
		if($status>50){ $type = ($status>80) ? 'danger' : 'warning'; }else{ $type = 'success'; }
		include(MB_STYLE.'progress.html');
		return ob_get_clean();
	}

	private static function setINFO($text, $url='', $type=4){
		$_SESSION['mb_info'] = $text;
		$_SESSION['mb_info_t'] = $type;
		header('Location: '.MB_URL.$url); exit;
		return true;
	}

	// $core->pagination('MYSQL_TABLE', 'RESULTS_ON_PAGE', $MOD.'page-', "WHERE cid='5'");
	public static function pagination($table, $res=MB_ROP, $page='', $where=''){
		global $cfg;
		ob_start();

		if(isset($_GET['pid'])){$pid = intval($_GET['pid']);}else{$pid = 1;}
		$start = $pid * $res - $res; if($table===0 || $res===0 || $page===0 || $where===0){ return $start; }
		$query = MB_QUERY("SELECT COUNT(*) FROM `$table` $where");
		$ar = MFA($query);
		$max = intval(ceil($ar[0] / $res));
		if($pid<=0 || $pid>$max){ return ob_get_clean(); }
		if($max>1)
		{
			
			
			$FirstPge='<li><a href="'.MB_URL.$page.'1"><<</a></li>';
			if($pid-2>0){$Prev2Pge	='<li><a href="'.MB_URL.$page.($pid-2).'">'.($pid-2).'</a></li>';}else{$Prev2Pge ='';}
			if($pid-1>0){$PrevPge	='<li><a href="'.MB_URL.$page.($pid-1).'">'.($pid-1).'</a></li>';}else{$PrevPge ='';}
			$SelectPge = '<li><a href="'.MB_URL.$page.$pid.'"><b>'.$pid.'</b></a></li>';
			if($pid+1<=$max){$NextPge	='<li><a href="'.MB_URL.$page.($pid+1).'">'.($pid+1).'</a></li>';}else{$NextPge ='';}
			if($pid+2<=$max){$Next2Pge	='<li><a href="'.MB_URL.$page.($pid+2).'">'.($pid+2).'</a></li>';}else{$Next2Pge ='';}
			$LastPge='<li><a href="'.MB_URL.$page.$max.'">>></a></li>';
			include(MB_STYLE."pagination.html");
		}

		return ob_get_clean();
	}

	private function folders(){
		ob_start();
		$query = MB_QUERY("SELECT url,title FROM `mb_cats` WHERE uid='".MB_UID."'");
		if($query && MNR($query)>0){
			while($ar = MFA($query)){
				$url = HSC($ar['url']);
				$title = HSC($ar['title']);
				include(MB_STYLE.'folder.html');
			}
		}
		$this->num_folders = MNR($query);
		return ob_get_clean();
	}

	public function mb_object(){
		ob_start();
		$var = base64_decode('Y2xhc3M9Im1iX2NvcHkiPk1haWxCb3ggwqkgPGEgaHJlZj0iaHR0cDovL3FleHkub3JnIj5RZXh5Lm9yZzwvYT4=');
		echo '<div '.$var.'</div>';
		return ob_get_clean();
	}

	public function main(){
		ob_start();
		$folders = $this->folders();
		include_once(MB_STYLE.'main.html');
		return ob_get_clean();
	}

	private function messages($folder){
		ob_start();
		$folder = intval($folder);

		$start = self::pagination(0);

		$query = MB_QUERY("SELECT id, title, fid, `tid`, `read`, `to`, `from`, `date`
							FROM `mb_messages`
							WHERE uid='".MB_UID."' AND folder='$folder'
							ORDER BY `date` DESC LIMIT $start,".MB_ROP."");
		if($query && MNR($query)>0){
			while($ar = MFA($query)){
				$id = intval($ar['id']);
				$title = HSC($ar['title']);
				$from = HSC($ar['from']);
				$fid = intval($ar['fid']);
				$to = HSC($ar['to']);
				$tid = intval($ar['tid']);
				$date = date("d.m.Y в H:i:s", $ar['date']);
				$read = (intval($ar['read'])==0) ? 'unread' : 'read';
				$hint = (intval($ar['read'])==0) ? 'Не прочитано' : 'Прочитано';

				include(MB_STYLE.'message.html');
			}

			$query = MB_QUERY("SELECT COUNT(*) FROM `mb_messages` WHERE uid='".MB_UID."' AND folder='$folder'");
			$ar = MFA($query);
			$this->num_messages = $ar[0];
		}else{
			include_once(MB_STYLE.'message-none.html');
		}
		return ob_get_clean();
	}

	public function box($box, $val, $path, $trash=false){
		ob_start();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(isset($_POST['action'], $_POST['value'], $_POST['item'])){
				$action = intval($_POST['action']);
				if(!is_array($_POST['item'])){ $_POST['item'] = explode(',', $_POST['item']); }
				$array = array();
				foreach ($_POST['item'] as $value){ $array[] = intval($value); }
				$imp_array = implode(',', $array);
				$num_array = count($array);

				if($action==1){
					if($trash===true){
						$update = MB_QUERY("DELETE FROM `mb_messages` WHERE uid='".MB_UID."' AND `id` IN ($imp_array)");
					}else{
						if(!self::is_full(-3, MB_UID, $num_array)){ self::setINFO('Ошибка! Папка переполнена!', '', 3); }
						$update = MB_QUERY("UPDATE `mb_messages` SET folder='-3' WHERE uid='".MB_UID."' AND `id` IN ($imp_array)");
					}
				}elseif($action==2){
					if(isset($_POST['tobox'])){
						$tobox = intval($_POST['tobox']);
						if($tobox == -1 || $tobox == -2 || $tobox == -3 || $tobox == -4){
							$query = true;
						}else{
							$query = MB_QUERY("SELECT COUNT(*) FROM `mb_cats` WHERE id='$tobox'");
							if(!$query){ self::setINFO('Действие не выполнено', '', 3); }
							$ar = MFA($query);
							$query = ($ar[0]>0) ? true : false;
						}

						if(!$query){ self::setINFO('Error! Debug - <b>S-2</b>', '', 3); }
						if(!self::is_full($tobox, MB_UID, $num_array)){ self::setINFO('Ошибка! Папка переполнена!', '', 3); }
						$update = MB_QUERY("UPDATE `mb_messages` SET folder='$tobox' WHERE uid='".MB_UID."' AND `id` IN ($imp_array)");
						if(!$update){ self::setINFO('Error! Debug - <b>S-3</b>', '', 3); }
						self::setINFO('Действие успешно выполнено', '', 1);
					}
					$boxs = self::box_options();
					include_once(MB_STYLE.'box-move.html');
					return ob_get_clean();

				}else{
					self::setINFO('Error! Debug - <b>H-1</b>', '', 3);
				}

				if(!$update){ self::setINFO('Error! Debug - <b>S-4</b>', '', 3); }

				self::setINFO('Действие успешно выполнено', '', 1);

			}else{
				self::setINFO('Error! Debug - <b>H-2</b>', '', 3);
			}
		}
		
		$messages = $this->messages($val);
		$pagination = self::pagination("mb_messages", MB_ROP, $path."/", "WHERE uid='".MB_UID."' AND folder='$val'");

		include_once(MB_STYLE.'box-full.html');
		return ob_get_clean();
	}

	private static function box_options(){
		ob_start();
		$query = MB_QUERY("SELECT id, title FROM `mb_cats` WHERE uid='".MB_UID."'");
		if(!$query){ self::setINFO('Error! Debug - <b>S-5</b>', '', 3); }
		if($query && MNR($query)>0){
			while($ar = MFA($query)){
				$id = intval($ar['id']);
				$title = HSC($ar['title']);
				echo '<option value="'.$id.'">'.$title.'</option>';
			}
		}
		return ob_get_clean();
	}

	public function message(){
		ob_start();

		if(!isset($_GET['mid'])){ self::setINFO('Сообщение не выбрано!'); }
		$id = intval($_GET['mid']);

		$query = MB_QUERY("SELECT title, `date`, text_html, `from`, `read`, `to`, `fid`, `tid`
							FROM `mb_messages`
							WHERE uid='".MB_UID."' AND id='$id'");
		if(!$query || MNR($query)<=0){ self::setINFO('Сообщение недоступно! Debug - <b>H-3</b>', '', 3); }

		$ar			= MFA($query);

		$title		= HSC($ar['title']);
		$date		= date("d.m.Y в H:i:s", $ar['date']);
		$message	= $ar['text_html'];
		$read		= intval($ar['read']);
		$from		= HSC($ar['from']);
		$to			= HSC($ar['to']);
		$fid		= intval($ar['fid']);
		$tid		= intval($ar['tid']);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['delete'])){
				$delete = MB_QUERY("DELETE FROM `mb_messages` WHERE uid='".MB_UID."' AND id='$id'");
				if(!$delete){ self::setINFO('Error! Debug - <b>S-6</b>', 'message/'.$id, 3); }
				self::setINFO('Сообщение успешно удалено!', '', 0);
			}
		}

		if($read==0){
			$update = MB_QUERY("UPDATE `mb_messages` SET `read`='1' WHERE uid='".MB_UID."' AND id='$id'");
			if(!$update){ self::setINFO('Error! Debug - <b>S-7</b>', 'message/'.$id, 3); }
		}

		include_once(MB_STYLE.'message-full.html');

		return ob_get_clean();
	}

	public function other(){
		ob_start();

		$url = MRES($_GET['do']);

		$query = MB_QUERY("SELECT id, title FROM `mb_cats` WHERE url='$url'");

		if(!$query || MNR($query)<=0){ self::setINFO('Error! Debug - <b>H-4</b>', '', 3); }

		$ar = MFA($query);
		$id = intval($ar['id']);
		$box = HSC($ar['title']);

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(isset($_POST['action'], $_POST['value'], $_POST['item'])){
				$action = intval($_POST['action']);
				if(!is_array($_POST['item'])){ $_POST['item'] = explode(',', $_POST['item']); }
				$array = array();
				foreach ($_POST['item'] as $value){ $array[] = intval($value); }
				$imp_array = implode(',', $array);
				$num_array = count($array);

				if($action==1){
					if(!self::is_full(-3, MB_UID, $num_array)){ self::setINFO('Ошибка! Папка переполнена!', '', 3); }
					$update = MB_QUERY("UPDATE `mb_messages` SET folder='-3' WHERE uid='".MB_UID."' AND `id` IN ($imp_array)");
				}elseif($action==2){
					if(isset($_POST['tobox'])){
						$tobox = intval($_POST['tobox']);
						if($tobox == -1 || $tobox == -2 || $tobox == -3 || $tobox == -4){
							$query = true;
						}else{
							$query = MB_QUERY("SELECT COUNT(*) FROM `mb_cats` WHERE id='$tobox'");
							if(!$query){ self::setINFO('Действие не выполнено', '', 3); }
							$ar = MFA($query);
							$query = ($ar[0]>0) ? true : false;
						}

						if(!$query){ self::setINFO('Действие не выполнено', '', 3); }
						if(!self::is_full($tobox, MB_UID, $num_array)){ self::setINFO('Ошибка! Папка переполнена!', '', 3); }
						$update = MB_QUERY("UPDATE `mb_messages` SET folder='$tobox' WHERE uid='".MB_UID."' AND `id` IN ($imp_array)");
						if(!$update){ self::setINFO('Error! Debug - <b>S-8</b>', '', 3); }
						self::setINFO('Действие успешно выполнено', '', 1);
					}
					$boxs = self::box_options();
					include_once(MB_STYLE.'box-move.html');
					return ob_get_clean();

				}else{
					self::setINFO('Error! Debug - <b>H-5</b>', '', 3);
				}

				if(!$update){ self::setINFO('Error! Debug - <b>S-9</b>', '', 3); }

				self::setINFO('Действие успешно выполнено', '', 1);

			}else{
				self::setINFO('Error! Debug - <b>H-6</b>', '', 3);
			}
		}

		$messages = $this->messages($id);
		$pagination = self::pagination("mb_messages", MB_ROP, $url."/", "WHERE uid='".MB_UID."' AND folder='$id'");

		include_once(MB_STYLE.'box-full.html');
		return ob_get_clean();
	}

	public function send(){
		global $bd_names, $bd_users;
		ob_start();
		$to = $subject = $message = '';

		//$query = MB_QUERY("SELECT COUNT(*) FROM ");

		if($_SERVER['REQUEST_METHOD'] == 'POST'){

			if(isset($_POST['submit']) || isset($_POST['drafts'])){
				$to			= MRES($_POST['to']);

				$query		= MB_QUERY("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$to'");
				if(!$query || MNR($query)<=0){ self::setINFO('Пользователь не существует!', 'send/', 3); }
				$ar			= MFA($query);

				$title		= MRES($_POST['subject']);
				$text_bb	= HSC($_POST['message']);
				$text_html	= self::decodeBB($text_bb);
				$date		= time();
				$fid		= MB_UID;
				$tid		= intval($ar[$bd_users['id']]);
				$uid		= intval($ar[$bd_users['id']]);
				$from		= MB_UNAME;
			}

			if(isset($_POST['submit'])){
				if(!self::is_full(-1, $tid)){ self::setINFO('Ошибка! Папка "Входящие" у пользователя "'.$to.'" переполнена!', 'send/', 3); }
				if(!self::is_full(-2, $fid)){ self::setINFO('Ошибка! Папка "Исходящие" переполнена!', 'send/', 3); }
				$insert1	= MB_QUERY("INSERT INTO `mb_messages`
											(title, `date`, text_bb, text_html, uid, fid, tid, `from`, `to`)
										VALUES
											('$title', '$date', '$text_bb', '$text_html', '$tid', '$fid', '$tid', '$from', '$to')");

				$insert2	= MB_QUERY("INSERT INTO `mb_messages`
											(title, `date`, text_bb, text_html, folder, uid, fid, tid, `from`, `to`)
										VALUES
											('$title', '$date', '$text_bb', '$text_html', '-2', '$fid', '$fid', '$tid', '$from', '$to')");

				if(!$insert1 || !$insert2){ self::setINFO('Error! Debug - <b>S-10</b>', 'send/', 3); }

				self::setINFO('Сообщение успешно отправлено!', 'send/', 1);
			}elseif(isset($_POST['drafts'])){
				if(!self::is_full(-4, $fid)){ self::setINFO('Папка "Черновики" переполнена!', 'send/', 3); }
				$insert			= MB_QUERY("INSERT INTO `mb_messages`
											(title, `date`, text_bb, text_html, folder, uid, fid, tid, `from`, `to`)
										VALUES
											('$title', '$date', '$text_bb', '$text_html', '-4', '$fid', '$fid', '$tid', '$from', '$to')");

				if(!$insert){ self::setINFO('Error! Debug - <b>S-11</b>', 'send/', 3); }

				self::setINFO('Сообщение успешно сохранено!', 'send/', 1);
			}elseif(isset($_POST['resend'])){
				$id = intval($_POST['resend']);
				$query = MB_QUERY("SELECT title, text_bb FROM `mb_messages` WHERE uid='".MB_UID."' AND id='$id'");
				if(!$query || MNR($query)<=0){ self::setINFO('Сообщение недоступно! Debug - <b>H-7</b>', 'send/', 3); }
				$ar = MFA($query);
				$subject = HSC($ar['title']);
				$message = HSC($ar['text_bb']);
			}elseif(isset($_POST['reply'])){
				$id = intval($_POST['reply']);
				$query = MB_QUERY("SELECT title, text_bb, `from` FROM `mb_messages` WHERE uid='".MB_UID."' AND id='$id'");
				if(!$query || MNR($query)<=0){ self::setINFO('Сообщение недоступно! Debug - <b>H-8</b>', 'send/', 3); }
				$ar = MFA($query);
				$to = HSC($ar['from']);
				$subject = HSC($ar['title']);
				$message = '[quote]'.HSC($ar['text_bb']).'[/quote]';
			}elseif(isset($_POST['to'])){
				$to = HSC($_POST['to']);
			}else{
				self::setINFO('Hacking Attempt!', 'send/', 3);
			}
		}

		include_once(MB_STYLE.'send.html');
		return ob_get_clean();
	}

	private function array_boxs(){
		ob_start();

		$query = MB_QUERY("SELECT * FROM `mb_cats` WHERE uid='".MB_UID."'");

		if(!$query || MNR($query)<=0){
			include_once(MB_STYLE.'box-list-none.html');
		}else{
			while($ar = MFA($query)){
				$id = intval($ar['id']);
				$title = HSC($ar['title']);
				$url = HSC($ar['url']);
				include(MB_STYLE.'box-list.html');
			}
		}
		$this->num_folders = MNR($query);

		return ob_get_clean();
	}

	private static function convertToLatin($text){
		$text = mb_strtolower($text, 'UTF-8');
		
		$tr = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
			'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
			'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
			'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
			'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
			'ш' => 'sh', 'щ' => 'sh', 'ы' => 'i', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
		);

		$text = strtr($text, $tr);
		$text = preg_replace('/[^\\pL0-9_]+/u', '-', $text);
		$text = trim($text, "-");
		$text = iconv("utf-8", "us-ascii//TRANSLIT", $text);
		$text = preg_replace('/[^-a-z0-9_]+/', '', $text);

		if(empty($text)){ $text = self::passgen(10, true);}
		return $text;
	}

	private static function passgen($length=6, $param=false) {
		$chars = "abcdefghijklmnopqrstuvwxyz";

		if(!$param){ $chars .= 'ABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789$*()-+=#@!'; }

		$string = "";

		$len = strlen($chars) - 1;  
		while (strlen($string) < $length){
			$string .= $chars[mt_rand(0,$len)];  
		}

		return $string;
	}

	public function newbox(){
		global $mb_functions;
		ob_start();

		$id = $title = $url = '';
		$btn = 'Добавить';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['id']) && !empty($_POST['id'])){
				if(isset($_POST['submit'])){
					$id		= intval($_POST['id']);
					$title	= MRES($_POST['title']);
					$url	= MRES($_POST['url']);
					$update	= MB_QUERY("UPDATE `mb_cats` SET title='$title', url='$url' WHERE uid='".MB_UID."' AND id='$id'");
					if(!$update){ self::setINFO('Error! Debug - <b>S-12</b>', 'new/', 3); }
					self::setINFO("Папка успешно изменена!", 'new/');
				}
			}elseif(isset($_POST['edit'])){
				$id = intval($_POST['edit']);

				$query = MB_QUERY("SELECT title, url FROM `mb_cats` WHERE uid='".MB_UID."' AND id='$id'");
				if(!$query || MNR($query)<=0){ self::setINFO('Error! Debug - <b>H-9</b>', 'new/', 3); }

				$ar = MFA($query);

				$title = HSC($ar['title']);
				$url = HSC($ar['url']);
				$btn = 'Сохранить';

			}elseif(isset($_POST['delete'])){
				$id = intval($_POST['delete']);
				$delete1	= MB_QUERY("DELETE FROM `mb_cats` WHERE uid='".MB_UID."' AND id='$id'");
				$delete2	= MB_QUERY("DELETE FROM `mb_messages` WHERE uid='".MB_UID."' AND folder='$id'");
				if(!$delete1 || !$delete2){ self::setINFO('Error! Debug - <b>S-13</b>', 'new/', 3); }
				self::setINFO('Успешно удалено!', 'new/', 1);
			}
		}

		$boxlist = $this->array_boxs();

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['id']) || empty($_POST['id'])){
				if(isset($_POST['submit'])){
					if($this->num_folders>=MB_FOLDERS){ self::setINFO('Вы исчерпали лимит на добавление папок!', 'new/', 3); }
					$title	= trim($_POST['title']);

					if(!isset($_POST['title']) || empty($title)) { self::setINFO('Поле "Название" заполнено неверно!', 'new/', 3); }

					if(!isset($_POST['url']) || empty($_POST['url'])){
						$url= self::convertToLatin($title);
					}else{
						$url= self::convertToLatin($_POST['url']);
					}

					$title	= MRES($title);
					$url	= MRES($url);
					$query	= MB_QUERY("SELECT COUNT(*) FROM `mb_cats` WHERE uid='".MB_UID."' AND url='$url'");
					if(!$query){ self::setINFO('Error! Debug - <b>S-14</b>', 'new/', 3); }
					$ar		= MFA($query);

					if(in_array($url, $mb_functions)){ self::setINFO('Ошибка! Недопустимое имя папки.', 'new/', 3); }

					if($ar[0]>0){
						self::setINFO('Ошибка! Папка с таким URL уже существует.', 'new/', 3);
					}

					$insert	= MB_QUERY("INSERT INTO `mb_cats` (title, url, uid) VALUES ('$title', '$url', '".MB_UID."')");
					if(!$insert){ self::setINFO('Error! Debug - <b>S-15</b>', 'new/', 3); }
					self::setINFO('Новая папка успешно создана!', 'new/', 1);
				}
			}else{
				self::setINFO('Hacking Attempt!', 'new/', 3);
			}
		}

		include_once(MB_STYLE.'newbox.html');
		return ob_get_clean();
	}
	
	private static function BBquote($text)
	{
		$reg = '#\[quote]((?:[^[]|\[(?!/?quote])|(?R))+)\[/quote]#isu';
		if (is_array($text)){$text = '<div class="quote">'.$text[1].'</div>';}
		return preg_replace_callback($reg, 'self::BBquote', $text);
	}

	private static function decodeBB($text)
	{
		$text = nl2br($text);
		$text = preg_replace('/\[b\](.*)\[\/b\]/Usi', '<b>$1</b>', $text);
		$text = preg_replace('/\[i\](.*)\[\/i\]/Usi', '<i>$1</i>', $text);
		$text = preg_replace('/\[s\](.*)\[\/s\]/Usi', '<s>$1</s>', $text);
		$text = preg_replace('/\[u\](.*)\[\/u\]/Usi', '<u>$1</u>', $text);
		$text = preg_replace('/\[left\](.*)\[\/left\]/Usi', '<p align="left">$1</p>', $text);
		$text = preg_replace('/\[center\](.*)\[\/center\]/Usi', '<p align="center">$1</p>', $text);
		$text = preg_replace('/\[right\](.*)\[\/right\]/Usi', '<p align="right">$1</p>', $text);
		$text = preg_replace("/\[url=(?:&#039;|&quot;|\'|\")(((ht|f)tps?:(?:\/\/)?)(?:[^<\s\'\"]+))(?:&#039;|&quot;|\'|\")\](.*)\[\/url\]/Usi", "<a href=\"$1\">$4</a>", $text);
		$text = preg_replace("/\[img\](((ht|f)tps?:(?:\/\/)?)(?:[^<\s\'\"]+))\[\/img\]/Usi", "<img src=\"$1\">", $text);
		$text = preg_replace("/(?<!:&#039;|&quot;|\'|\"|\])(((ht|f)tp(s)?:\/\/)([^<\s\'\"]+))(?<!:&#039;|&quot;|\'|\"|\[)/is", "<a href=\"$1\">$1</a>", $text);
		$text = preg_replace("/\[color=(?:&#039;|&quot;|\'|\")(\#[a-z0-9]{6})(?:&#039;|&quot;|\'|\")\](.*)\[\/color\]/Usi", "<font color=\"$1\">$2</font>", $text);

		$text = self::BBquote($text);

		return $text;
	}

	private static function savecfg()
	{
		global $mbcfg;

		$txt  = '<?php'.PHP_EOL;
		$txt .= '$mbcfg = '.var_export($mbcfg, true).';'.PHP_EOL;
		$txt .= '?>';

		if(!is_writable(MCR_ROOT."mailbox.cfg.php")){ self::setINFO('Файл mailbox.cfg.php недоступен для записи!', '', 3); }
		$result = file_put_contents(MCR_ROOT."mailbox.cfg.php", $txt);

		if (is_bool($result) and $result == false){return false;}

		return true;
	}

	public function settings(){
		global $mbcfg;
		ob_start();
		if(MB_LVL<15){ self::setINFO('Доступ запрещен!', '', 3); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['submit'])){
				$mbcfg['max_folders'] = intval($_POST['max_folders']);
				$mbcfg['max_mof'] = intval($_POST['max_mof']);
				$mbcfg['rop'] = intval($_POST['rop']);
				if($mbcfg['max_folders']<=0 ||
					$mbcfg['max_mof']<=0 ||
					$mbcfg['rop']<=0 ){
					self::setINFO('Ошибка! Число не может быть меньше 1', 'settings/', 3);
				}

				if(!self::savecfg()){ self::setINFO('Ошибка сохранения настроек', 'settings/', 3); }

				self::setINFO('Настройки успешно сохранены', 'settings/', 1);
			}else{
				self::setINFO('Hacking Attempt!', 'settings/', 3);
			}
		}

		include_once(MB_STYLE.'settings.html');
		return ob_get_clean();
	}
}

/*
----------------------------------------
---- MailBox for webmcr by Qexy.org ----
---- Version: 2.0 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/
?>