<?php
/*
----------------------------------------
---- MailBox for webmcr by Qexy.org ----
---- Version: 2.0 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/

if (!defined('MCR') || empty($user)){ header("Location: ".BASE_URL); exit; }

$page = 'MailBox';
$menu->SetItemActive('qexy_mailbox');

require_once(MCR_ROOT.'mailbox.cfg.php');

$_SESSION['num_q'] = 0;

define('M_VERSION', '1.0');
define('MB_STYLE', STYLE_URL.'Default/mailbox/');
define('MB_UID', $player_id);
define('MB_UNAME', $player);
define('MB_LVL', $player_lvl);
define('MB_URL', BASE_URL.'go/mailbox/');
define('MB_FOLDERS', $mbcfg['max_folders']);
define('MB_MESSAGES', $mbcfg['max_mof']);
define('MB_ROP', $mbcfg['rop']);

$content_js .= '<link href="'.MB_STYLE.'css/mailbox.css" rel="stylesheet">';
$content_js .= '<script src="'.MB_STYLE.'js/mailbox.js"></script>';

function MFA($result){		return mysql_fetch_array($result);			}
function MNR($result){		return mysql_num_rows($result);				}
function MRES($result){		return mysql_real_escape_string($result);	}
function HSC($result){		return htmlspecialchars($result);			}
function MB_QUERY($query){	$_SESSION['num_q']++; return BD($query);	}

$mb_functions = array('send', 'main', 'inbox', 'outbox', 'trash', 'drafts', 'new', 'message', 'settings', 'install');

require_once(MCR_ROOT.'instruments/mailbox.class.php'); $mailbox = new MailBox;

if(isset($_SESSION['mb_info'])){ define('MB_INFO', $mailbox->INFO()); }else{ define('MB_INFO', ''); }

if(isset($_GET['do'])){ $mb_do = $_GET['do']; }else{ $mb_do = 'main'; }

if($mailbox->is_install()){ $mb_do = 'install'; }

switch($mb_do){
	case 'send':	$content_main = $mailbox->send();		break;
	case 'main':	$content_main = $mailbox->main();		break;
	case 'inbox':	$content_main = $mailbox->box('Входящие', -1, 'inbox', false);	break;
	case 'outbox':	$content_main = $mailbox->box('Исходящие', -2, 'outbox', false);break;
	case 'trash':	$content_main = $mailbox->box('Корзина', -3, 'trash', true);	break;
	case 'drafts':	$content_main = $mailbox->box('Черновики', -4, 'drafts', false);break;
	case 'new':		$content_main = $mailbox->newbox();		break;
	case 'message':	$content_main = $mailbox->message();	break;
	case 'settings':$content_main = $mailbox->settings();	break;
	case 'install':	$content_main = $mailbox->install();	break;
	default:		$content_main = $mailbox->other();		break;
}

$content_main .= $mailbox->mb_object();
//$content_main .= $_SESSION['num_q'];

unset($_SESSION['num_q']);
if(isset($_SESSION['mb_info'])){unset($_SESSION['mb_info']); unset($_SESSION['mb_info_t']);}

/*
----------------------------------------
---- MailBox for webmcr by Qexy.org ----
---- Version: 2.0 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/
?>