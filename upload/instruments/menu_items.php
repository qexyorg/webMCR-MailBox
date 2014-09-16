<?php if (!defined('MCR')) exit;

$menu_items = array (

  0 => array (
  
    'main' => array (
	
      'name' => 'Главная',
      'url' => '',
      'parent_id' => -1,
      'lvl' => -1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'guide' => array (
      'name' => 'Начать играть',
      'url' => Rewrite::GetURL('guide'),
      'parent_id' => -1,
      'lvl' => -1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'rules' => array (
	
      'name' => 'Правила',
      'url' => Rewrite::GetURL('rules'),
      'parent_id' => -1,
      'lvl' => -1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'options' => array (
	
      'name' => 'Настройки',
      'url' => Rewrite::GetURL('options'),
      'parent_id' => -1,
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
  ),
  
  1 => array (

    'qexy_mailbox' => array (
  
      'name' => '<i class="icon-envelope"></i>',
      'url' => Rewrite::GetURL('mailbox'),
      'parent_id' => -1,
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
  
    'q_send' => array (
  
      'name' => 'Написать',
      'url' => ($config['rewrite'])? 'go/mailbox/send' : '?mode=mailbox&do=send',
      'parent_id' => 'qexy_mailbox',
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
  
    'q_inbox' => array (
  
      'name' => 'Входящие',
      'url' => ($config['rewrite'])? 'go/mailbox/inbox' : '?mode=mailbox&do=inbox',
      'parent_id' => 'qexy_mailbox',
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'q_outbox' => array (
  
      'name' => 'Исходящие',
      'url' => ($config['rewrite'])? 'go/mailbox/outbox' : '?mode=mailbox&do=outbox',
      'parent_id' => 'qexy_mailbox',
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'q_trash' => array (
  
      'name' => 'Корзина',
      'url' => ($config['rewrite'])? 'go/mailbox/trash' : '?mode=mailbox&do=trash',
      'parent_id' => 'qexy_mailbox',
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'admin' => array (
	
      'name' => 'Администрирование',
      'url' => '',
      'parent_id' => -1,
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'add_news' => array (
	
      'name' => 'Добавить новость',
      'url' => Rewrite::GetURL('news_add'),
      'parent_id' => 'admin',
      'lvl' => 1,
      'permission' => 'add_news',
      'active' => false,
      'inner_html' => '',
    ),
	
    'control' => array (
	
      'name' => 'Пользователи',
      'url' => Rewrite::GetURL(array('control', 'user')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'category_news' => array (
	
      'name' => 'Категории новостей',
      'url' => Rewrite::GetURL(array('control', 'category')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'reg_edit' => array (
	
      'name' => 'Регистрация',
      'url' => Rewrite::GetURL(array('control', 'ipbans')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'group_edit' => array (
	
      'name' => 'Группы',
      'url' => Rewrite::GetURL(array('control', 'group')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
    'file_edit' => array (
	
      'name' => 'Файлы',
      'url' => Rewrite::GetURL(array('control', 'filelist')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
    'site_edit' => array (
	
      'name' => 'Сайт',
      'url' => Rewrite::GetURL(array('control', 'constants')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
    'rcon' => array (
	
      'name' => 'RCON',
      'url' => Rewrite::GetURL(array('control', 'rcon')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'game_edit' => array (
	
      'name' => 'Настройки игры',
      'url' => Rewrite::GetURL(array('control', 'update')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),
	
    'serv_edit' => array (
	
      'name' => 'Мониторинг серверов',
      'url' => Rewrite::GetURL(array('control', 'servers')),
      'parent_id' => 'admin',
      'lvl' => 15,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',
    ),

    'exit' => array (
	
      'name' => 'Выход',
      'url' => 'login.php?out=1',
      'parent_id' => -1,
      'lvl' => 1,
      'permission' => -1,
      'active' => false,
      'inner_html' => '',	  
    ),
	
  ),
);
