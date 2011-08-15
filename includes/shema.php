<?php
/**
 * Схема таблиц данного модуля.
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage Support
 * @copyright Copyright (C) 2011 Abricos. All rights reserved.
 * @author  Alexander Kuzmin (roosit@abricos.org)
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = CMSRegistry::$instance->modules->updateManager; 
$db = CMSRegistry::$instance->db;
$pfx = $db->prefix;

$uprofileManager = CMSRegistry::$instance->modules->GetModule('uprofile')->GetManager(); 

if ($updateManager->isInstall()){
	
	$uprofileManager->FieldAppend('lastname', 'Фамилия', UserFieldType::STRING, 100);
	$uprofileManager->FieldAppend('firstname', 'Имя', UserFieldType::STRING, 100);
	$uprofileManager->FieldCacheClear();
	
	CMSRegistry::$instance->modules->GetModule('support')->permission->Install();
	
	// проекты
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."spt_message (
		  `messageid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор сообщения',
  		  `pubkey` varchar(32) NOT NULL DEFAULT '' COMMENT 'Уникальный публичный ключ',
		  `isprivate` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Приватная запись',
		  `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',
		  `title` varchar(250) NOT NULL DEFAULT '' COMMENT 'Название',
		  `contentid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор контента сообщения',

		  `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время создания',
		  `upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',
		  
		  `status` int(2) unsigned NOT NULL DEFAULT 0 COMMENT 'Текущий статус записи',
		  `statuserid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь текущего статуса',
		  `statdate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время текущего статуса',

		  `cmtcount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во комменатрий',
		  `cmtuserid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь последнего комментария',
		  `cmtdate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время последнего комментария',
		  
		  PRIMARY KEY  (`messageid`)
		)".$charset
	);
	
	// Прикрепленные файлы к сообщению
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."spt_file (
		  `fileid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
		  `messageid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения',
		  `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя',
		  `filehash` varchar(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор файла таблицы fm_file',
		  PRIMARY KEY  (`fileid`), 
		  UNIQUE KEY `file` (`messageid`,`filehash`)
		)".$charset
	);

}

?>