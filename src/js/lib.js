var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'uprofile', files: ['users.js']},
        {name: 'sys', files: ['item.js']},
        {name: 'support', files: ['functions.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModer: 40,
        isWrite: 30,
        isView: 10
    });

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        TMG = this.template,
        NS = this.namespace,
        R = NS.roles;

    var buildTemplate = this.buildTemplate;
    buildTemplate({}, '');

    // дополнить эксперементальными функциями менеджер шаблонов
    var TMP = Brick.Template.Manager.prototype;
    TMP.elHide = function(els){
        this.elShowHide(els, false);
    };
    TMP.elShow = function(els){
        this.elShowHide(els, true);
    };
    TMP.elShowHide = function(els, show){
        if (L.isString(els)){
            var arr = els.split(','), tname = '';
            els = [];
            for (var i = 0; i < arr.length; i++){
                var arr1 = arr[i].split('.');
                if (arr1.length == 2){
                    tname = L.trim(arr1[0]);
                    els[els.length] = L.trim(arr[i]);
                }
                els[els.length] = tname + '.' + L.trim(arr[i]);
            }
        }
        if (!L.isArray(els)){
            return;
        }
        for (var i = 0; i < els.length; i++){
            var el = this.getEl(els[i]);
            Dom.setStyle(el, 'display', show ? '' : 'none');
        }
    };

    var MessageStatus = {
        'OPENED': 0,	// открыта
        'CLOSED': 1,	// закрыта
        'REMOVED': 2		// удалена
    };
    NS.MessageStatus = MessageStatus;

    var Message = function(data){
        this.init(data);
    };
    Message.prototype = {
        init: function(d){

            d = L.merge({
                'id': 0,
                'tl': '',
                'dl': 0,
                'uid': Brick.env.user.id
            }, d || {});

            this.update(d);

            // была ли загрузка оставшихся данных?
            this.isLoad = false;

            // описание задачи
            this.body = '';
            this.files = [];
        },
        update: function(d){
            this.id = d['id'] * 1;								// идентификатор
            this.title = d['tl'];								// заголовок
            this.userid = d['uid'];								// идентификатор автора
            this.date = NS.dateToClient(d['dl']); 				// дата создания

            this.updDate = NS.dateToClient(d['udl']); 			// дата создания

            this.cmt = (L.isNull(d['cmt']) ? 0 : d['cmt']) * 1;	// кол-во сообщений
            this.cmtDate = NS.dateToClient(d['cmtdl']);			// дата последнего сообщения
            this.cmtUserId = L.isNull(d['cmtuid']) ? 0 : d['cmtuid'];	// дата последнего сообщения

            this.status = d['st'] * 1;
            this.stUserId = d['stuid'];
            this.stDate = NS.dateToClient(d['stdl']);
        },
        setData: function(d){
            this.isLoad = true;
            this.body = d['bd'];
            this.ctid = d['ctid'];
            this.files = d['files'];
            this.update(d);
        },

        isRemoved: function(){
            return this.status * 1 == MessageStatus.REMOVED;
        },

        isClosed: function(){
            return this.status * 1 == MessageStatus.CLOSED;
        }
    };
    NS.Message = Message;

    var MessageList = function(data){
        MessageList.superclass.constructor.call(this, data);
    };
    YAHOO.extend(MessageList, NS.List, {});
    NS.MessageList = MessageList;

    var Manager = function(inda){
        this.init(inda);
    };
    Manager.prototype = {
        init: function(inda){

            this._hlid = 0;
            this.messagesChangedEvent = new YAHOO.util.CustomEvent("messagesChangedEvent");

            this.list = new MessageList();
            this.listUpdate(inda['board']);

            this.users = new NS.UserList(inda['users']);

            this.lastUpdateTime = new Date();

            E.on(document.body, 'mousemove', this.onMouseMove, this, true);
        },
        onMouseMove: function(evt){
            var ctime = (new Date()).getTime(), ltime = this.lastUpdateTime.getTime();

            if ((ctime - ltime) / (1000 * 60) < 3){
                return;
            }
            // if ((ctime-ltime)/(1000) < 5){ return; }

            this.lastUpdateTime = new Date();

            // получения времени сервера необходимое для синхронизации
            // и проверка обновлений в задачах
            this.ajax({'do': 'sync'}, function(r){
            });
        },

        listUpdate: function(data){
            // обновить данные по сообщениям: новые - создать, существующие - обновить
            var objs = {},
                n = [], // новые
                u = [], // обновленые
                d = []; // удаленные

            var hlid = this._hlid * 1;

            for (var id in data){
                var di = data[id];
                hlid = Math.max(di['udl'] * 1, hlid);
                var message = this.list.find(id);
                if (L.isNull(message)){ // новая задача
                    message = new Message(di);
                    this.list.add(message);
                    n[n.length] = message;
                } else {
                    message.update(di);
                    u[u.length] = message;
                }
                objs[id] = message;
            }
            this._hlid = hlid;
            return {
                'n': n,
                'u': u,
                'd': d
            };
        },

        _ajaxBeforeResult: function(r){
            if (L.isNull(r)){
                return null;
            }
            if (r.u * 1 != Brick.env.user.id){ // пользователь разлогинился
                Brick.Page.reload();
                return null;
            }

            var chgs = r['changes'];

            if (L.isNull(chgs)){
                return null;
            } // изменения не зафиксированы

            this.users.update(chgs['users']);
            return this.listUpdate(chgs['board']);
        },

        _ajaxResult: function(upd){
            if (L.isNull(upd)){
                return null;
            }
            if (upd['n'].length == 0 && upd['u'].length == 0 && upd['d'].length == 0){
                return null;
            }
            this.messagesChangedEvent.fire(upd);
        },

        ajax: function(d, callback){
            // d['hlid'] = this.history.lastId();
            d['hlid'] = this._hlid;

            // все запросы по модулю проходят через этот менеджер.
            // ко всем запросам добавляется идентификатор последнего обновления
            // если на сервере произошли изменения, то они будут
            // зафиксированны у этого пользователя
            var __self = this;
            Brick.ajax('support', {
                'data': d,
                'event': function(request){
                    if (L.isNull(request.data)){
                        return;
                    }

                    var upd = __self._ajaxBeforeResult(request.data);

                    // применить результат запроса
                    callback(request.data.r);

                    __self._ajaxResult(upd);
                }
            });
        },
        _messageAJAX: function(messageid, cmd, callback){
            callback = callback || function(){
                };
            var __self = this;
            this.ajax({'do': cmd, 'messageid': messageid}, function(r){
                __self._setLoadedMessageData(r);
                callback(r);
            });
        },
        _setLoadedMessageData: function(d){
            if (L.isNull(d)){
                return;
            }
            var message = this.list.find(d['id']);
            if (L.isNull(message)){
                return;
            }

            message.setData(d);
        },
        messageLoad: function(messageid, callback){
            callback = callback || function(){
                };
            var message = this.list.find(messageid);

            if (L.isNull(message) || message.isLoad){
                callback();
                return true;
            }
            this._messageAJAX(messageid, 'message', callback);
        },
        messageSave: function(message, d, callback){
            callback = callback || function(){
                };
            var __self = this;

            d = L.merge({
                'id': 0, 'title': '',
                'body': '',
                'files': {}
            }, d || {});

            var dmessage = {
                'id': message.id,
                'tl': d['title'],
                'bd': d['body'],
                'files': d['files']
            };
            this.ajax({
                'do': 'messagesave',
                'message': dmessage
            }, function(r){
                __self._setLoadedMessageData(r);
                callback(r);
            });
        },
        messageClose: function(messageid, callback){ // закрыть сообщение
            this._messageAJAX(messageid, 'messageclose', callback);
        },
        messageRemove: function(messageid, callback){ // удалить сообщение
            this._messageAJAX(messageid, 'messageremove', callback);
        }

    };
    NS.supportManager = null;


    NS.buildManager = function(callback){
        if (!L.isNull(NS.supportManager)){
            callback(NS.supportManager);
            return;
        }
        R.load(function(){
            Brick.ajax('support', {
                'data': {'do': 'init'},
                'event': function(request){
                    NS.supportManager = new Manager(request.data);
                    callback(NS.supportManager);
                }
            });
        });
    };

    var GlobalMenuWidget = function(container, page){
        this.init(container, page);
    };
    GlobalMenuWidget.prototype = {
        init: function(container, page){
            buildTemplate(this, 'gbmenu');

            container.innerHTML = this._TM.replace('gbmenu', {
                'list': page == 'list' ? 'current' : '',
                'config': page == 'config' ? 'current' : ''
            });
        }
    };
    NS.GlobalMenuWidget = GlobalMenuWidget;

};