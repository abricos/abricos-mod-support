<?php
/**
 * @package Abricos
 * @subpackage Support
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class SupportModule
 */
class SupportModule extends Ab_Module {

    private $_manager = null;

    public function __construct(){
        $this->version = "0.1.4";
        $this->name = "support";
        $this->takelink = "support";
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

    /**
     * This module added menu item in BOS Panel
     *
     * @return bool
     */
    public function Bos_IsMenu(){
        return true;
    }

}


class SupportAction {
    const VIEW = 10;
    const WRITE = 30;
    const MODER = 40;
    const ADMIN = 50;
}

class SupportGroup {

    /**
     * Группа "Модераторы"
     *
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
     *
     * @var integer
     */
    const OPENED = 0;

    /**
     * Закрыто
     *
     * @var integer
     */
    const CLOSED = 1;

    /**
     * Удалено
     *
     * @var integer
     */
    const REMOVED = 2;
}


class SupportPermission extends Ab_UserPermission {

    public function SupportPermission(SupportModule $module){

        $defRoles = array(
            new Ab_UserRole(SupportAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(SupportAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(SupportAction::WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(SupportAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(SupportAction::MODER, SupportGroup::MODERATOR),

            new Ab_UserRole(SupportAction::ADMIN, Ab_UserGroup::ADMIN)
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

Abricos::ModuleRegister(new SupportModule());

?>