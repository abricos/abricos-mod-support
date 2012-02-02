<?php
/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2011 Abricos. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


if (empty(Abricos::$user->id)){ return;  }

$modFM = Abricos::GetModule('filemanager');
if (empty($modFM)){ return; }

$brick = Brick::$builder->brick;
$var = &$brick->param->var;

if (Abricos::$adress->dir[2] !== "go"){ return; }

$uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('image');
$uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);
$error = $uploadFile->Upload();

if ($error == 0){
	$var['command'] = Brick::ReplaceVarByData($var['ok'], array(
		"fhash" => $uploadFile->uploadFileHash,
		"fname" => $uploadFile->fileName
	));
}else{
	$var['command'] = Brick::ReplaceVarByData($var['error'], array(
		"errnum" => $error
	));

	$brick->content = Brick::ReplaceVarByData($brick->content, array(
		"fname" => $uploadFile->fileName
	));
}
	
?>