<?php 
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Support
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class SupportModule extends CMSModule {
	
	public function __construct(){
		$this->version = "0.1";
		$this->name = "support";
		$this->permission = new SupportPermission($this);
	}
	
	/**
	 * Получить менеджер
	 *
	 * @return SupportManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new SupportManager($this);
		}
		return $this->_manager;
	}
	
	public function GetContentName(){
		$cname = '';
		$adress = $this->registry->adress;
		
		if ($adress->level >= 2 && $adress->dir[1] == 'upload'){
			$cname = $adress->dir[1];
		}
		return $cname;
	}	
	
}


class SupportAction {
	const VIEW	= 10;
	const WRITE	= 30;
	const MODER	= 40;
	const ADMIN	= 50;
}

class SupportGroup {
	
	/**
	 * Группа "Модераторы"
	 * @var string
	 */
	const MODERATOR = 'support_moderator';
}


/**
 * Статус задачи
 */
class SupportStatus {
	
	/**
	 * Открыто
	 * @var integer
	 */
	const OPENED = 0;

	/**
	 * Закрыто
	 * @var integer
	 */
	const CLOSED = 1;
	
	/**
	 * Удалено
	 * @var integer
	 */
	const REMOVED = 2;
}


class SupportPermission extends AbricosPermission {
	
	public function SupportPermission(SupportModule $module){
		
		$defRoles = array(
			new AbricosRole(SupportAction::VIEW, UserGroup::REGISTERED),
			new AbricosRole(SupportAction::VIEW, UserGroup::ADMIN),
			
			new AbricosRole(SupportAction::WRITE, UserGroup::REGISTERED),
			new AbricosRole(SupportAction::WRITE, UserGroup::ADMIN),
			
			new AbricosRole(SupportAction::MODER, SupportGroup::MODERATOR),
			
			new AbricosRole(SupportAction::ADMIN, UserGroup::ADMIN)
		);
		parent::__construct($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			SupportAction::VIEW => $this->CheckAction(SupportAction::VIEW),
			SupportAction::WRITE => $this->CheckAction(SupportAction::WRITE),
			SupportAction::MODER => $this->CheckAction(SupportAction::MODER),
			SupportAction::ADMIN => $this->CheckAction(SupportAction::ADMIN)
		);
	}
}

$mod = new SupportModule();
CMSRegistry::$instance->modules->Register($mod);

?>