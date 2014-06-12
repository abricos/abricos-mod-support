/*
@package Abricos
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: 'support', files: ['lib.js']}
	]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace, 
		TMG = this.template,
		API = NS.API,
		R = NS.roles;
	
	var UP = Brick.mod.uprofile;
	
	var LNG = Brick.util.Language.getc('mod.support');

    var buildTemplate = this.buildTemplate;

	var TST = NS.MessageStatus;
	
	var MessageListWidget = function(container){
		this.init(container);
	};
	MessageListWidget.prototype = {
		init: function(container){
		
			NS.supportManager.messagesChangedEvent.subscribe(this.onMesssagesChangedEvent, this, true);

			this.list = NS.supportManager.list;
			
			buildTemplate(this, 'widget,table,row,user,empttitle');
			container.innerHTML = this._TM.replace('widget');

			var __self = this;
			E.on(container, 'click', function(e){
                if (__self._onClick(E.getTarget(e))){ E.preventDefault(e); }
			});
			this.render();
		},
		
		onMesssagesChangedEvent: function(e1, e2){
			this.render();
		},
		
		render: function(){
			
			var TM = this._TM, 
				lst = "";
			
			var arr = [];
			this.list.foreach(function(msg){
				arr[arr.length] = msg;
			});
			arr = arr.sort(function(m1, m2){
				var t1 = m1.updDate.getTime(),
					t2 = m2.updDate.getTime();
				
				if (!L.isNull(m1.cmtDate)){
					t1 = Math.max(t1, m1.cmtDate.getTime());
				}
				if (!L.isNull(m2.cmtDate)){
					t2 = Math.max(t2, m2.cmtDate.getTime());
				}
				if (t1 > t2){ return -1; }
				if (t1 < t2){ return 1; }
				return 0;
			});
			for (var i=0; i<arr.length; i++){
				var msg = arr[i];
				
				var user = NS.supportManager.users.get(msg.userid);
				var d = {
					'id': msg.id,
					'tl': msg.title.length > 0 ? msg.title : TM.replace('empttitle'),
					'cmt': msg.cmt,
					'cmtuser': TM.replace('user', {'uid': user.id, 'unm': user.getUserName()}),
					'cmtdate': Brick.dateExt.convert(msg.updDate),
					'closed': msg.isClosed() ? 'closed' : '',
					'removed': msg.isRemoved() ? 'removed' : ''
				};
				if (msg.cmt > 0){
					var user = NS.supportManager.users.get(msg.cmtUserId);
					d['cmtuser'] =  TM.replace('user', {'uid': user.id, 'unm': user.getUserName()});
					d['cmtdate'] = Brick.dateExt.convert(msg.cmtDate);
				}
				
				lst += TM.replace('row', d);
			}
			TM.getEl('widget.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		
		_onClick: function(el){
			return false;
		},

		destroy: function(){
			NS.supportManager.messagesChangedEvent.unsubscribe(this.onMesssagesChangedEvent);
		}
	};
	NS.MessageListWidget = MessageListWidget;
	
	
};