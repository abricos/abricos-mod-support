/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2011 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js', 'editor.js']},
        {name: 'support', files: ['lib.js', 'roles.js']},
        {name: 'filemanager', files: ['lib.js']}
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

	var initCSS = false, buildTemplate = function(w, ts){
		if (!initCSS){
			Brick.util.CSS.update(Brick.util.CSS['support']['msgeditor']);
			delete Brick.util.CSS['support']['msgeditor'];
			initCSS = true;
		}
		w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;
	};
	
	var FilesWidget = function(container, owner){
		this.init(container, owner);
	};
	FilesWidget.prototype = {
		init: function(container, owner){
			this.owner = owner;
			this.uploadWindow = null;
			
			buildTemplate(this, 'files,ftable,frow');
			container.innerHTML = this._TM.replace('files');
			this.files = owner.message.files;
			this.showButtons(false);
			this.render();
		},
		onClick: function(el){
			var TId = this._TId, tp = TId['files'];
			switch(el.id){
			case tp['bshowbtnsex']:
			case tp['bshowbtns']: this.showButtons(true); return true;
			case tp['bcancel']: this.showButtons(); return true;
			case tp['bshowfm']: this.showFileBrowser(); return true;
			case tp['bupload']: this.fileUpload(); return true;
			}
			
			var arr = el.id.split('-');
			if (arr.length == 2 && arr[0] == TId['frow']['remove']){
				this.removeFile(arr[1]);
				return true;
			}
			return false;
		},
		showButtons: function(en){
			var TM = this._TM;
			TM.elShowHide('files.bshowbtns,bshowbtnsex', false);
			if (this.files.length > 0){
				TM.elShowHide('files.bshowbtnsex', !en);
			}else{
				TM.elShowHide('files.bshowbtns', !en);
			}
			TM.elShowHide('files.fm', en);
		},
		showFileBrowser: function(){
			var __self = this;
			Brick.f('filemanager', 'api', 'showFileBrowserPanel', function(result){
				var fi = result['file'];
				__self.appendFile({
					'id': fi['id'],
					'nm': fi['name'],
					'sz': fi['size']
				});
        	});
		},
		removeFile: function(fid){
			var fs = this.files, nfs = [];
			
			for (var i=0; i<fs.length; i++){
				if (fs[i]['id'] != fid){
					nfs[nfs.length] = fs[i];
				}
			}
			this.files = nfs;
			this.showButtons(false);
			this.render();
		},
		appendFile: function(fi){
			var fs = this.files;
			for (var i=0; i<fs.length; i++){
				if (fs[i]['id'] == fi['id']){ return; }
			}
			fs[fs.length] = fi;
			this.showButtons(false);
			this.render();
		},
		render: function(){
			var TM = this._TM, lst = "", fs = this.files;
			
			for (var i=0; i<fs.length; i++){
				var f = fs[i];
				var lnk = new Brick.mod.filemanager.Linker({
					'id': f['id'],
					'name': f['nm']
				});
				lst	+= TM.replace('frow', {
					'fid': f['id'],
					'nm': f['nm'],
					'src': lnk.getSrc()
				});
			}
			TM.getEl('files.table').innerHTML = fs.length > 0 ? TM.replace('ftable', {
				'rows': lst 
			}) : "";
		},
		fileUpload: function(){
			if (!L.isNull(this.uploadWindow) && !this.uploadWindow.closed){
				this.uploadWindow.focus();
				return;
			}
			var url = '/support/upload/';
			this.uploadWindow = window.open(
				url, 'catalogimage',	
				'statusbar=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=480,height=270' 
			); 
		},
		setFileByFID: function(fid, fname){
			this.appendFile({
				'id': fid,
				'nm': fname
			});
		}
	};
	
	var MessageEditorPanel = function(messageid){
		
		this.messageid = messageid || 0;
		
		MessageEditorPanel.active = this;

		MessageEditorPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(MessageEditorPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel,frow');

			return this._TM.replace('panel');
		},
		onLoad: function(){
			var __self = this, TM = this._TM;
			this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');
			NS.buildManager(function(man){
				__self.onBuildManager();
			});
		},
		onBuildManager: function(){
			var TM = this._TM,
				message = this.messageid == 0 ? new NS.Message() : NS.supportManager.list.find(this.messageid);
				__self = this;
			
			this.message = message;
			
			Dom.setStyle(TM.getEl('panel.tl'+(message.id*1 > 0 ? 'new' : 'edit')), 'display', 'none');
			
			TM.getEl('panel.tl').value = message.title;
			TM.getEl('panel.editor').innerHTML = message.body; 
			
			var Editor = Brick.widget.Editor;
			this.editor = new Editor(this._TId['panel']['editor'], {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
			
			this.filesWidget = new FilesWidget(TM.getEl('panel.files'), this);
		},
		destroy: function(){
			this.editor.destroy();
			MessageEditorPanel.active = null;
			MessageEditorPanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			if (this.filesWidget.onClick(el)){ return true; }
			var TId = this._TId, tp = TId['panel'];
			switch(el.id){
			case tp['bsave']: this.saveMessage(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			return false;
		},
		saveMessage: function(){
			var TM = this._TM,
				message = this.message;
			
			Dom.setStyle(TM.getEl('panel.bsave'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.bcancel'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.loading'), 'display', '');
			
			var newdata = {
				'title': TM.getEl('panel.tl').value,
				'body': this.editor.getContent(),
				'files': this.filesWidget.files
			};

			var __self = this;
			NS.supportManager.messageSave(message, newdata, function(d){
				d = d || {};
				var messageid = (d['id'] || 0)*1;

				__self.close();
			});
		}
	});
	NS.MessageEditorPanel = MessageEditorPanel;

	// создать сообщение
	API.showCreateMessagePanel = function(){
		return NS.API.showMessageEditorPanel(0);
	};

	var activePanel = null;
	NS.API.showMessageEditorPanel = function(messageid, pmessageid){
		if (!L.isNull(activePanel) && !activePanel.isDestroy()){
			activePanel.close();
		}
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new MessageEditorPanel(messageid, pmessageid);
		}
		return activePanel;
	};
};