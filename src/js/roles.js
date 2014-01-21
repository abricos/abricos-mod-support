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
		mn = this.moduleName;

	NS.roles = {
		load: function(callback){
			BP.load(function(){
				var r = NS.roles;
				r['isAdmin'] = BP.check(mn, '50')==1; // Админ
				r['isModer'] = BP.check(mn, '40')==1 || NS.roles['isAdmin']; // Модератор
				r['isWrite'] = BP.check(mn, '30')==1 || NS.roles['isModer']; // Запись
				r['isView'] = BP.check(mn, '10')==1 || r['isWrite']; // Чтение
				callback();
			});
		}
	};
	
};