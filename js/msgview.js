/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2011 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'filemanager', files: ['lib.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: 'support', files: ['lib.js']}
	]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		NS = this.namespace, 
		TMG = this.template,
		API = NS.API,
		R = NS.roles;
	
	var LNG = Brick.util.Language.getc('mod.support'),
		MST = NS.MessageStatus;

	var initCSS = false, buildTemplate = function(w, ts){
		if (!initCSS){
			Brick.util.CSS.update(Brick.util.CSS['support']['msgview']);
			delete Brick.util.CSS['support']['msgview'];
			initCSS = true;
		}
		w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;
	};
	
	var aTargetBlank = function(el){
		if (el.tagName == 'A'){
			el.target = "_blank";
		}else if (el.tagName == 'IMG'){
			el.style.maxWidth = "100%";
			el.style.height = "auto";
		}
		var chs = el.childNodes;
		for (var i=0;i<chs.length;i++){
			if (chs[i]){ aTargetBlank(chs[i]); }
		}
	};
	
	var MessageViewPanel = function(messageid){
		this.message = NS.supportManager.list.find(messageid);

// TODO: если this.message=null необходимо показать "либо нет прав, либо проект удален"
		
		MessageViewPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(MessageViewPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel,user,frow');
			
			var message = this.message;

			return this._TM.replace('panel', {
				'id': message.id,
				'tl': message.title
			});
		},
		onLoad: function(){
			var message = this.message,
				TM = this._TM,
				__self = this;
			
			this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');
			
			this.firstLoad = true;
			
			// запросить дополнительные данные - описание
			NS.supportManager.messageLoad(message.id, function(){
				__self.renderMessage();
			});
		},
		destroy: function(){
			MessageViewPanel.superclass.destroy.call(this);
		},
		renderMessage: function(){
			var TM = this._TM, message = this.message, 
				__self = this, 
				gel = function(nm){ return TM.getEl('panel.'+nm); };
			
			gel('messagebody').innerHTML = message.body;
			
			if (this.firstLoad){ // первичная рендер
				this.firstLoad = false;
				
				// Инициализировать менеджер комментариев
				Brick.ff('comment', 'comment', function(){
					Brick.mod.comment.API.buildCommentTree({
						'container': TM.getEl('panel.comments'),
						'dbContentId': message.ctid,
						'config': {
							'onLoadComments': function(){
								aTargetBlank(TM.getEl('panel.messagebody'));
								aTargetBlank(TM.getEl('panel.comments'));
							}
							// ,
							// 'readOnly': project.w*1 == 0,
							// 'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
						},
						'instanceCallback': function(b){ }
					});
				});
			}

			var elColInfo = gel('colinfo');
			for (var i=1;i<=5;i++){
				Dom.removeClass(elColInfo, 'status'+i);
			}
			Dom.addClass(elColInfo, 'status'+message.status);

			// Статус
			gel('status').innerHTML = LNG['status'][message.status];
			
			// Автор
			var user = NS.supportManager.users.get(message.userid);
			gel('author').innerHTML = TM.replace('user', {
				'uid': user.id, 'unm': user.getUserName()
			});
			// Создана
			gel('dl').innerHTML = Brick.dateExt.convert(message.date, 3, true);
			gel('dlt').innerHTML = Brick.dateExt.convert(message.date, 4);

			// закрыть все кнопки, открыть по ролям 
			TM.elHide('panel.bopen,bclose,beditor,bremove');
			
			var isMyMessage = user.id*1 == Brick.env.user.id*1;
			if (message.status == MST.OPENED){
				if (R['isModer']){
					TM.elShow('panel.beditor,bremove,bclose'); 
				}else if (isMyMessage){
					TM.elShow('panel.beditor,bremove'); 
				}
			}
			
			var fs = message.files;
			// показать прикрепленные файлы
			if (fs.length > 0){
				TM.elShow('panel.files');
				
				var alst = [], lst = "";
				for (var i=0;i<fs.length;i++){
					var f = fs[i];
					var lnk = new Brick.mod.filemanager.Linker({
						'id': f['id'],
						'name': f['nm']
					});
					alst[alst.length] = TM.replace('frow', {
						'fid': f['id'],
						'nm': f['nm'],
						'src': lnk.getSrc()
					});
				}
				lst = alst.join('');
				TM.getEl('panel.ftable').innerHTML = lst;
			}else{
				TM.elHide('panel.files');
			}
		},
		onClick: function(el){
			var tp = this._TId['panel'];
			switch(el.id){
			
			case tp['beditor']: this.messageEditorShow(); return true;
			
			case tp['bclose']: 
			case tp['bclosens']: 
				this.messageClose(); return true;
			
			case tp['bcloseno']: this.messageCloseCancel(); return true;
			case tp['bcloseyes']: this.messageCloseMethod(); return true;
			
			/*
			
			case tp['bsetexec']: this.setExecMessage(); return true;
			case tp['bunsetexec']: this.unsetExecMessage(); return true;
			

			case tp['bremove']: 
				this.messageRemove(); return true;
			
			case tp['bremoveno']: this.messageRemoveCancel(); return true;
			case tp['bremoveyes']: this.messageRemoveMethod(); return true;

			case tp['brestore']: 
				this.messageRestore(); return true;

			case tp['barhive']: 
				this.messageArhive(); return true;

			case tp['bopen']:  this.messageOpen(); return true;
			/**/
			}
			return false;
		},
		_shLoading: function(show){
			var TM = this._TM;
			TM.elShowHide('panel.buttons', !show);
			TM.elShowHide('panel.bloading', show);
		},
		
		
		// закрыть сообщение
		messageClose: function(){ 
			var TM = this._TM;
			TM.elHide('panel.manbuttons');
			TM.elShow('panel.dialogclose');
		},
		messageCloseCancel: function(){
			var TM = this._TM;
			TM.elShow('panel.manbuttons');
			TM.elHide('panel.dialogclose');
		},
		messageCloseMethod: function(){
			this.messageCloseCancel();
			var __self = this;
			this._shLoading(true);
			NS.supportManager.messageClose(this.message.id, function(){
				__self._shLoading(false);
			});
		}
		
		
		/*
		,

		
		
		messageRemoveCancel: function(){
			var TM = this._TM;
			TM.elShow('panel.manbuttons');
			TM.elHide('panel.dialogremove');
		},
		messageRemove: function(){
			var TM = this._TM;
			TM.elHide('panel.manbuttons');
			TM.elShow('panel.dialogremove');
		},
		messageRemoveMethod: function(){
			this.messageRemoveCancel();
			var __self = this;
			this._shLoading(true);
			NS.supportManager.messageRemove(this.message.id, function(){
				__self._shLoading(false);
			});
		},
		messageRestore: function(){
			var __self = this;
			this._shLoading(true);
			NS.supportManager.messageRestore(this.message.id, function(){
				__self._shLoading(false);
			});
		},
		messageArhive: function(){
			var __self = this;
			this._shLoading(true);
			NS.supportManager.messageArhive(this.message.id, function(){
				__self._shLoading(false);
			});
		},
		
		messageOpen: function(){ // открыть проект повторно
			var __self = this;
			this._shLoading(true);
			NS.supportManager.messageOpen(this.message.id, function(){
				__self._shLoading(false);
			});
		},
		messageEditorShow: function(){
			var messageid = this.message.id;
			Brick.ff('support', 'messageeditor', function(){
				API.showMessageEditorPanel(messageid);
			});
		}
		
		/**/
	});
	NS.MessageViewPanel = MessageViewPanel;
	
	API.showMessageViewPanel = function(messageid){
		NS.buildManager(function(){
			new MessageViewPanel(messageid);
		});
	};

};