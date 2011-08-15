/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[{name: 'user', files: ['permission.js']}]
};
Component.entryPoint = function(){
	
	var NS = this.namespace,
		BP = Brick.Permission,
		moduleName = this.moduleName;;

	NS.roles = {
		load: function(callback){
			BP.load(function(){
				NS.roles['isAdmin'] = BP.check(moduleName, '50') == 1; // Админ
				NS.roles['isModer'] = BP.check(moduleName, '40') == 1; // Модератор
				NS.roles['isWrite'] = BP.check(moduleName, '30') == 1; // Запись
				NS.roles['isView'] = BP.check(moduleName, '10') == 1; // Чтение
				callback();
			});
		}
	};
	
};