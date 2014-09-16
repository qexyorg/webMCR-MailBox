<?php
$sql1 = MB_QUERY("CREATE TABLE IF NOT EXISTS `{$config['db_name']}`.`mb_cats` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `url` varchar(30) CHARACTER SET latin1 NOT NULL,
  `title` varchar(100) NOT NULL,
  `uid` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$sql2 = MB_QUERY("CREATE TABLE IF NOT EXISTS `{$config['db_name']}`.`mb_messages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `date` int(10) NOT NULL,
  `text_bb` text NOT NULL,
  `text_html` text NOT NULL,
  `folder` int(10) NOT NULL DEFAULT '-1',
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL,
  `fid` int(10) NOT NULL,
  `tid` int(10) NOT NULL,
  `from` varchar(64) CHARACTER SET latin1 NOT NULL,
  `to` varchar(64) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
?>