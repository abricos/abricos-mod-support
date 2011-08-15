<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Support
 * @copyright Copyright (C) 2011 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

class SupportManager extends ModuleManager {
	
	/**
	 * @var SupportModule
	 */
	public $module = null;
	
	/**
	 * User
	 * @var User
	 */
	public $user = null;
	public $userid = 0;
	
	/**
	 * @var SupportManager
	 */
	public static $instance = null; 
	
	public function SupportManager(SupportModule $module){
		parent::ModuleManager($module);
		
		$this->user = CMSRegistry::$instance->modules->GetModule('user');
		$this->userid = $this->user->info['userid'];
		SupportManager::$instance = $this;
	}
	
	public function IsAdminRole(){
		return $this->module->permission->CheckAction(SupportAction::ADMIN) > 0;
	}
	
	public function IsModerRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->module->permission->CheckAction(SupportAction::MODER) > 0;
	}
	
	public function IsWriteRole(){
		if ($this->IsModerRole()){ return true; }
		return $this->module->permission->CheckAction(SupportAction::WRITE) > 0;
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->module->permission->CheckAction(SupportAction::VIEW) > 0;
	}
	
	private function _AJAX($d){
		
		switch($d->do){
			case 'messagesave': return $this->MessageSave($d->message);
			case 'message': return $this->Message($d->messageid);
			case 'sync': return $this->Sync();
			
			/*
			case 'messagesetexec': return $this->MessageSetExec($d->messageid);
			case 'messageunsetexec': return $this->MessageUnsetExec($d->messageid);
			case 'messageclose': return $this->MessageClose($d->messageid);
			case 'messageremove': return $this->MessageRemove($d->messageid);
			case 'messagerestore': return $this->MessageRestore($d->messageid);
			case 'messagearhive': return $this->MessageArhive($d->messageid);
			case 'messageopen': return $this->MessageOpen($d->messageid);
			case 'messagevoting': return $this->MessageVoting($d->messageid, $d->val);
			case 'messagefavorite': return $this->MessageFavorite($d->messageid, $d->val);
			case 'messageexpand': return $this->MessageExpand($d->messageid, $d->val);
			case 'messageshowcmt': return $this->MessageShowComments($d->messageid, $d->val);
			case 'history': return $this->History($d->socid, $d->firstid);
			case 'usercfgupdate': return $this->UserConfigUpdate($d->cfg);
			case 'lastcomments': return $this->CommentList();
			/**/
		}
		return null;
	}
	
	public function AJAX($d){
		if ($d->do == "init"){
			return $this->BoardData(0);
		}
		$ret = new stdClass();
		$ret->u = $this->userid;
		$ret->r = $this->_AJAX($d);
		$ret->changes = $this->BoardData($d->hlid);
		
		return $ret;
	}
	
	private function ToArray($rows){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row['id']] = $row;
		}
		return $ret;
	}
	
	public function Sync(){ return TIMENOW; }
	
	public function BoardData($lastupdate = 0){
		if (!$this->IsViewRole()){ return null; }
		$ret = new stdClass();
		$ret->board = array();
		$ret->hlid = $lastupdate;
		
		$uids = array();
		
		$rows = SupportQuery::MessageList($this->db, $this->userid, $this->IsModerRole(), $lastupdate);
		while (($row = $this->db->fetch_array($rows))){
			$ret->hlid = max($ret->hlid, intval($row['udl']));
			
			// время последнего комментария тоже участвует в определении изменений
			$ret->hlid = max($ret->hlid, intval($row['cmtdl']));
			
			$uids[$row['uid']] = true;
			$uids[$row['cmtuid']] = true;
			
			$ret->board[$row['id']] = $row;
		}
		if ($lastupdate == 0 || ($lastupdate > 0 && count($uids) > 0)){
			$uids[$this->userid] = true;
		}
		$ret->users = array();
		if (count($uids) > 0){
			$rows = SupportQuery::Users($this->db, $uids);
			$ret->users = $this->ToArray($rows);
		}
		
		return $ret;
	}	
	
	
	
	/**
	 * Сохранить сообщение
	 * 
	 * @param object $msg
	 */
	public function MessageSave($msg){
		
		if (!$this->IsWriteRole()){ return null; }
		
		$msg->id = intval($msg->id);
		
		$utmanager = CMSRegistry::$instance->GetUserTextManager();
		$msg->tl = $utmanager->Parser($msg->tl);
		if (!$this->IsAdminRole()){
			// порезать теги у описания
			$msg->bd = $utmanager->Parser($msg->bd);
		}
		
		$sendNewNotify = false;
		
		if ($msg->id == 0){
			$msg->uid = $this->userid;
			$pubkey = md5(time().$this->userid);
			$msg->id = SupportQuery::MessageAppend($this->db, $msg, $pubkey);
			
			$sendNewNotify = true;
		}else{
			$info = $this->Message($msg->id);
			if (!$this->MessageAccess($info)){
				return null;
			}
			
			if ($info['st'] == SupportStatus::CLOSED ||
				$info['st'] == SupportStatus::REMOVED ){ 
				return null; 
			}
			
			SupportQuery::MessageUpdate($this->db, $msg, $this->userid);
		}
		
		// обновить информацию по файлам
		$files = $this->MessageFiles($msg->id, true);
		$arr = $msg->files;

		foreach ($files as $rFileId => $cfile){
			$find = false;
			foreach ($arr as $file){
				if ($file->id == $rFileId){
					$find = true;
					break;
				}
			}
			if (!$find){
				SupportQuery::MessageFileRemove($this->db, $msg->id, $rFileId);
			}
		}
		foreach ($arr as $file){
			$find = false;
			foreach ($files as $rFileId => $cfile){
				if ($file->id == $rFileId){
					$find = true;
					break;
				}
			}
			if (!$find){
				SupportQuery::MessageFileAppend($this->db, $msg->id, $file->id, $this->userid);
			}
		}
		
		$messageid = $msg->id;
		
		$message = $this->Message($messageid);
		
		if ($sendNewNotify){
			// Отправить уведомление всем модераторам
			
			$brick = Brick::$builder->LoadBrickS('support', 'templates', null, null);
			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
			$plnk = "http://".$host."/bos/#app=support/msgview/showMessageViewPanel/".$message['id']."/";
			
			$rows = SupportQuery::ModeratorList($this->db);
			while (($user = $this->db->fetch_array($rows))){
				
				if ($user['id'] == $this->userid){ continue; }
				
				$email = $user['eml'];
				if (empty($email)){ continue; }
				
				$subject = Brick::ReplaceVarByData($brick->param->var['newprojectsubject'], array(
					"tl" => $message['tl']
				));
				$body = Brick::ReplaceVarByData($brick->param->var['newprojectbody'], array(
					"tl" => $message['tl'],
					"plnk" => $plnk,
					"unm" => $this->UserNameBuild($this->user->info),
					"prj" => $message['bd'],
					"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
				));
				CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
			}
		}
		
		return $message;
	}
	
	public function MessageAccess($msg){
		if (!$this->IsViewRole() || empty($msg)){ return false; }
		if ($this->IsModerRole()){ return true; }
		
		if ($msg['prt'] == 1 && $this->userid != $msg['uid']){ return false; }
		
		return true;
	}
	
	public function Message($messageid){
		$msg = SupportQuery::Message($this->db, $messageid, true);
		if (!$this->MessageAccess($msg)){ return null; }
		
		$msg['files'] = array();
		$files = $this->MessageFiles($messageid, true);
		foreach ($files as $file){
			array_push($msg['files'], $file);
		}
		return $msg;
	}
		
	public function MessageFiles($messageid, $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		$rows = SupportQuery::MessageFiles($this->db, $messageid);
		if (!$retarray){ return $rows; }
		return $this->ToArray($rows);
	}
	
	////////////////////////////// комментарии /////////////////////////////
	public function CommentList(){
		if (!$this->IsViewRole()){ return null; }
		
		$rows = SupportQuery::CommentList($this->db, $this->userid);
		return $this->ToArray($rows);
	}
	
	public function IsCommentList($contentid){
		if (!$this->IsViewRole()){ return null; }
		$message = SupportQuery::MessageByContentId($this->db, $contentid, true);
		return $this->MessageAccess($message);
	}
	
	public function IsCommentAppend($contentid){
		$message = SupportQuery::MessageByContentId($this->db, $contentid, true);
		if (!$this->MessageAccess($message)){ return false; }
		if ($message['st'] == SupportStatus::CLOSED || $message['st'] == SupportStatus::REMOVED){ return false; }
		
		return true;
	}
	
	private function UserNameBuild($user){
		$firstname = !empty($user['fnm']) ? $user['fnm'] : $user['firstname']; 
		$lastname = !empty($user['lnm']) ? $user['lnm'] : $user['lastname']; 
		$username = !empty($user['unm']) ? $user['unm'] : $user['username'];
		return (!empty($firstname) && !empty($lastname)) ? $firstname." ".$lastname : $username;
	}
	
	/**
	 * Отправить уведомление о новом комментарии.
	 * 
	 * @param object $data
	 */
	public function CommentSendNotify($data){
		if (!$this->IsViewRole()){ return; }
		
		// данные по комментарию:
		// $data->id	- идентификатор комментария
		// $data->pid	- идентификатор родительского комментария
		// $data->uid	- пользователь оставивший комментарий
		// $data->bd	- текст комментария
		// $data->cid	- идентификатор контента

		$message = SupportQuery::MessageByContentId($this->db, $data->cid, true);
		if (!$this->MessageAccess($message)){ return; }
		
		// комментарий добавлен, необходимо обновить инфу
		SupportQuery::MessageCommentInfoUpdate($this->db, $message['id']);
		
		
		$brick = Brick::$builder->LoadBrickS('support', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$plnk = "http://".$host."/bos/#app=support/msgview/showMessageViewPanel/".$message['id']."/";


		$emails = array();
		
		// уведомление "комментарий на комментарий"
		if ($data->pid > 0){
			$parent = CommentQuery::Comment($this->db, $data->pid, $data->cid, true);
			if (!empty($parent) && $parent['uid'] != $this->userid){
				$user = UserQuery::User($this->db, $parent['uid']);
				$email = $user['email'];
				if (!empty($email)){
					$emails[$email] = true;
					$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlanssubject'], array(
						"tl" => $message['tl']
					));
					$body = Brick::ReplaceVarByData($brick->param->var['cmtemlansbody'], array(
						"tl" => $message['tl'],
						"plnk" => $plnk,
						"unm" => $this->UserNameBuild($this->user->info),
						"cmt1" => $parent['bd']." ",
						"cmt2" => $data->bd." ",
						"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
					));
					CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
				}
			}
		}
		
		// уведомление автору
		if ($message['uid'] != $this->userid){
			$autor = UserQuery::User($this->db, $message['uid']);
			$email = $autor['email'];
			if (!empty($email) && !$emails[$email]){
				$emails[$email] = true;
				$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
					"tl" => $message['tl']
				));
				$body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
					"tl" => $message['tl'],
					"plnk" => $plnk,
					"unm" => $this->UserNameBuild($this->user->info),
					"cmt" => $data->bd." ",
					"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
				));
				CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
			}
		}
				
		// уведомление модераторам
		$rows = SupportQuery::ModeratorList($this->db);
		while (($user = $this->db->fetch_array($rows))){
			$email = $user['eml'];
			
			if (empty($email) || $emails[$email] || $user['id'] == $this->userid){
				continue;
			}
			$emails[$email] = true;
			$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlsubject'], array(
				"tl" => $message['tl']
			));
			$body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
				"tl" => $message['tl'],
				"plnk" => $plnk,
				"unm" => $this->UserNameBuild($this->user->info),
				"cmt" => $data->bd." ",
				"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
			));
			CMSRegistry::$instance->GetNotification()->SendMail($email, $subject, $body);
		}
	}		
	

	
	
	
	
	
	
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	/**
	 * Список участников в проекте
	 * 
	 * @param integer $messageid идентификатор проекта
	 * @param boolean $retarray
	 */
	public function MessageUserList($messageid, $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		$rows = SupportQuery::MessageUserList($this->db, $messageid);
		if (!$retarray){ return $rows; }
		return $this->ToArray($rows);
	}
	
	/**
	 * Список участников в проекте с расшириными полями для отправки уведомлений
	 * 
	 * @param integer $messageid идентификатор проекта
	 * @param boolean $retarray
	 */
	private function MessageUserListForNotify($messageid, $retarray = false){
		$rows = SupportQuery::MessageUserListForNotify($this->db, $messageid);
		if (!$retarray){ return $rows; }
		return $this->ToArray($rows);
	}
	
	/**
	 * Завершить задачу
	 * 
	 * @param integer $messageid
	 */
	public function MessageClose($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		// сначало закрыть все подзадачи
		$rows = SupportQuery::Board($this->db, $this->userid, 0, $messageid);
		while (($row = $this->db->fetch_array($rows))){
			$this->MessageClose($row['id']);
		}
		
		$message = SupportQuery::Message($this->db, $messageid, $this->userid, true);
		
		if ($message['st'] == SupportStatus::DRAW_CLOSE){ return null; }
		
		$history = new SupportHistory($this->userid);
		$history->SetStatus($message, SupportStatus::DRAW_CLOSE, $this->userid);
		$history->Save();
		
		SupportQuery::MessageSetStatus($this->db, $messageid, SupportStatus::DRAW_CLOSE, $this->userid);
		
		return $this->Message($messageid);
	}
	
	/**
	 * Удалить задачу
	 * 
	 * @param integer $messageid
	 */
	public function MessageRemove($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		// сначало закрыть все подзадачи
		$rows = SupportQuery::Board($this->db, $this->userid, 0, $messageid);
		while (($row = $this->db->fetch_array($rows))){
			$this->MessageRemove($row['id']);
		}
		
		$message = SupportQuery::Message($this->db, $messageid, $this->userid, true);
		
		if ($message['st'] == SupportStatus::DRAW_REMOVE){ return null; }
		
		$history = new SupportHistory($this->userid);
		$history->SetStatus($message, SupportStatus::DRAW_REMOVE, $this->userid);
		$history->Save();
		
		SupportQuery::MessageSetStatus($this->db, $messageid, SupportStatus::DRAW_REMOVE, $this->userid);
		
		return $this->Message($messageid);
	}
	
	/**
	 * Восстановить удаленную задачу
	 */
	public function MessageRestore($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		$message = SupportQuery::Message($this->db, $messageid, $this->userid, true);
		if ($message['st'] != SupportStatus::DRAW_REMOVE){ return null; }
		
		// восстановить задачу
		$rows = SupportQuery::MessageHistory($this->db, $messageid);
		$i=0; 
		$prevStatus=SupportStatus::DRAW_OPEN;
		while (($row = $this->db->fetch_array($rows))){
			if ($i == 1){
				$prevStatus = $row['st'];
				break;
			}
			$i++;
		}
		
		$history = new SupportHistory($this->userid);
		$history->SetStatus($message, $prevStatus, $this->userid);
		$history->Save();
		
		SupportQuery::MessageSetStatus($this->db, $messageid, $prevStatus, $this->userid);
		
		return $this->Message($messageid);
	}
	
	/**
	 * Открыть задачу повторно
	 * 
	 * @param integer $messageid
	 */
	public function MessageOpen($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		$message = SupportQuery::Message($this->db, $messageid, $this->userid, true);
		
		if ($message['st'] != SupportStatus::DRAW_CLOSE &&  
			$message['st'] != SupportStatus::DRAW_REMOVE ){ 
			return null; 
		}
		
		$history = new SupportHistory($this->userid);
		$history->SetStatus($message, SupportStatus::DRAW_REOPEN, $this->userid);
		$history->Save();
		
		SupportQuery::MessageSetStatus($this->db, $messageid, SupportStatus::DRAW_REOPEN, $this->userid);
		
		return $this->Message($messageid);
	}
	
	/**
	 * Переместить задачу в архив
	 * 
	 * @param integer $messageid
	 */
	public function MessageArhive($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		$message = SupportQuery::Message($this->db, $messageid, $this->userid, true);
		
		if ($message['st'] != SupportStatus::DRAW_CLOSE){ return null; }
		
		$history = new SupportHistory($this->userid);
		$history->SetStatus($message, SupportStatus::DRAW_ARHIVE, $this->userid);
		$history->Save();
		
		SupportQuery::MessageSetStatus($this->db, $messageid, SupportStatus::DRAW_ARHIVE, $this->userid);
		
		return $this->Message($messageid);
	}
	
	public function MessageVoting($messageid, $value){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		SupportQuery::MessageVoting($this->db, $messageid, $this->userid, $value);
		
		return $value;
	}
	
	public function MessageFavorite($messageid, $value){
		if (!$this->MessageAccess($messageid)){ return null; }
		
		SupportQuery::MessageFavorite($this->db, $messageid, $this->userid, $value);
		
		return $value;
	}

	public function MessageExpand($messageid, $value){
		if (!$this->MessageAccess($messageid)){ return null; }
		SupportQuery::MessageExpand($this->db, $messageid, $this->userid, $value);
		return $value;
	}
	
	public function MessageShowComments($messageid, $value){
		if (!$this->MessageAccess($messageid)){ return null; }
		SupportQuery::MessageShowComments($this->db, $messageid, $this->userid, $value);
		return $value;
	}
	
	


	public function ImageList($messageid){
		if (!$this->MessageAccess($messageid)){ return null; }
		$rows = SupportQuery::ImageList($this->db, $messageid);
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$row['d'] = json_decode($row['d']);
			array_push($ret, $row);
		}
		return $ret;
	}

	public function History($messageid, $firstHId){
		if (!$this->IsViewRole()){ return null; }
		
		$messageid = intval($messageid);
		if ($messageid > 0){
			if (!$this->MessageAccess($messageid)){ return null; }
			$rows = SupportQuery::MessageHistory($this->db, $messageid, $firstHId);
		}else{
			$rows = SupportQuery::BoardHistory($this->db, $this->userid, 0, $firstHId);
		}
		$hst = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($hst, $row);
		}
		return $hst;
	}
	
}

?>