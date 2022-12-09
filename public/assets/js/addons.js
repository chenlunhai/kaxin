define([], function () {
    if (typeof Config.upload.storage !== 'undefined' && Config.upload.storage === 'alioss') {
    require(['upload', '../addons/alioss/js/spark'], function (Upload, SparkMD5) {
        //获取文件MD5值
        var getFileMd5 = function (file, cb) {
            var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice,
                chunkSize = 2097152,
                chunks = Math.ceil(file.size / chunkSize),
                currentChunk = 0,
                spark = new SparkMD5.ArrayBuffer(),
                fileReader = new FileReader();

            fileReader.onload = function (e) {
                spark.append(e.target.result);
                currentChunk++;
                if (currentChunk < chunks) {
                    loadNext();
                } else {
                    cb && cb(spark.end());
                }
            };

            fileReader.onerror = function () {
                console.warn('文件读取错误');
            };

            function loadNext() {
                var start = currentChunk * chunkSize,
                    end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
            }

            loadNext();
        };

        //初始化中完成判断
        Upload.events.onInit = function () {
            //如果上传接口不是阿里OSS，则不处理
            if (this.options.url !== Config.upload.uploadurl) {
                return;
            }
            $.extend(this.options, {
                //关闭自动处理队列功能
                autoQueue: false,
                params: function (files, xhr, chunk) {
                    var params = Config.upload.multipart;
                    if (chunk) {
                        return $.extend({}, params, {
                            filesize: chunk.file.size,
                            filename: chunk.file.name,
                            chunkid: chunk.file.upload.uuid,
                            chunkindex: chunk.index,
                            chunkcount: chunk.file.upload.totalChunkCount,
                            chunksize: this.options.chunkSize,
                            chunkfilesize: chunk.dataBlock.data.size,
                            width: chunk.file.width || 0,
                            height: chunk.file.height || 0,
                            type: chunk.file.type,
                            uploadId: chunk.file.uploadId,
                            key: chunk.file.key,
                        });
                    } else {
                        params = $.extend({}, params, files[0].params);
                    }
                    return params;
                },
                chunkSuccess: function (chunk, file, response) {
                    var etag = chunk.xhr.getResponseHeader("ETag").replace(/(^")|("$)/g, '');
                    this.etags = this.etags ? this.etags : [];
                    this.etags[chunk.index] = etag;
                },
                chunksUploaded: function (file, done) {
                    var that = this;

                    Fast.api.ajax({
                        url: "/addons/alioss/index/upload",
                        data: {
                            action: 'merge',
                            filesize: file.size,
                            filename: file.name,
                            chunkid: file.upload.uuid,
                            chunkcount: file.upload.totalChunkCount,
                            md5: file.md5,
                            key: file.key,
                            uploadId: file.uploadId,
                            etags: this.etags,
                            aliosstoken: Config.upload.multipart.aliosstoken,
                        },
                    }, function (data, ret) {
                        done(JSON.stringify(ret));
                        return false;
                    }, function (data, ret) {
                        file.accepted = false;
                        that._errorProcessing([file], ret.msg);
                        return false;
                    });

                },
            });

            var _success = this.options.success;
            //先移除已有的事件
            this.off("success", _success).on("success", function (file, response) {
                var ret = {code: 0, msg: response};
                try {
                    if (response) {
                        ret = typeof response === 'string' ? JSON.parse(response) : response;
                    }
                    if (file.xhr.status === 200) {
                        var url = '/' + file.key;
                        ret = {code: 1, data: {url: url}};

                        Fast.api.ajax({
                            url: "/addons/alioss/index/notify",
                            data: {name: file.name, url: url, md5: file.md5, size: file.size, width: file.width || 0, height: file.height || 0, type: file.type, aliosstoken: Config.upload.multipart.aliosstoken}
                        }, function () {
                            return false;
                        }, function () {
                            return false;
                        });
                    }
                } catch (e) {
                    console.error(e);
                }
                _success.call(this, file, ret);
            });

            this.on("addedfile", function (file) {
                var that = this;
                setTimeout(function () {
                    if (file.status === 'error') {
                        return;
                    }
                    getFileMd5(file, function (md5) {
                        var chunk = that.options.chunking && file.size > that.options.chunkSize ? 1 : 0;
                        Fast.api.ajax({
                            url: "/addons/alioss/index/params",
                            data: {method: 'POST', md5: md5, name: file.name, type: file.type, size: file.size, chunk: chunk, chunksize: that.options.chunkSize, aliosstoken: Config.upload.multipart.aliosstoken},
                        }, function (data) {
                            file.md5 = md5;
                            file.id = data.id;
                            file.key = data.key;
                            file.date = data.date;
                            file.uploadId = data.uploadId;
                            file.policy = data.policy;
                            file.signature = data.signature;
                            file.partsAuthorization = data.partsAuthorization;
                            file.params = data;
                            if (file.status != 'error') {
                                //开始上传
                                that.enqueueFile(file);
                            } else {
                                that.removeFile(file);
                            }
                            return false;
                        });
                    });
                }, 0);
            });


            if (Config.upload.uploadmode === 'client') {
                var _method = this.options.method;
                var _url = this.options.url;
                this.options.method = function (files) {
                    if (files[0].upload.chunked) {
                        var chunk = null;
                        files[0].upload.chunks.forEach(function (item) {
                            if (item.status === 'uploading') {
                                chunk = item;
                            }
                        });
                        if (!chunk) {
                            return "POST";
                        } else {
                            return "PUT";
                        }
                    }
                    return _method;
                };
                this.options.url = function (files) {
                    if (files[0].upload.chunked) {
                        var chunk = null;
                        files[0].upload.chunks.forEach(function (item) {
                            if (item.status === 'uploading') {
                                chunk = item;
                            }
                        });
                        var index = chunk.dataBlock.chunkIndex;
                        // debugger;
                        this.options.headers = {"Authorization": "OSS " + files[0]['id'] + ":" + files[0]['partsAuthorization'][index], "x-oss-date": files[0]['date']};
                        if (!chunk) {
                            return Config.upload.uploadurl + "/" + files[0].key + "?uploadId=" + files[0].uploadId;
                        } else {
                            return Config.upload.uploadurl + "/" + files[0].key + "?partNumber=" + (index + 1) + "&uploadId=" + files[0].uploadId;
                        }
                    }
                    return _url;
                };
                this.on("sending", function (file, xhr, formData) {
                    var that = this;
                    if (file.upload.chunked) {
                        var _send = xhr.send;
                        xhr.send = function () {
                            var chunk = null;
                            file.upload.chunks.forEach(function (item) {
                                if (item.status == 'uploading') {
                                    chunk = item;
                                }
                            });
                            if (chunk) {
                                _send.call(xhr, chunk.dataBlock.data);
                            }
                        };
                    }
                });
            }
        };
    });
}

require.config({
    paths: {
        'async': '../addons/example/js/async',
        'BMap': ['//api.map.baidu.com/api?v=2.0&ak=mXijumfojHnAaN2VxpBGoqHM'],
    },
    shim: {
        'BMap': {
            deps: ['jquery'],
            exports: 'BMap'
        }
    }
});

require.config({
    paths: {
        'nkeditor': '../addons/nkeditor/js/customplugin',
        'nkeditor-core': '../addons/nkeditor/nkeditor.min',
        'nkeditor-lang': '../addons/nkeditor/lang/zh-CN',
    },
    shim: {
        'nkeditor': {
            deps: [
                'nkeditor-core',
                'nkeditor-lang'
            ]
        },
        'nkeditor-core': {
            deps: [
                'css!../addons/nkeditor/themes/black/editor.min.css',
                'css!../addons/nkeditor/css/common.css'
            ],
            exports: 'window.KindEditor'
        },
        'nkeditor-lang': {
            deps: [
                'nkeditor-core'
            ]
        }
    }
});
require(['form'], function (Form) {
    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        if ($(".editor", form).size() > 0) {
            require(['nkeditor', 'upload'], function (Nkeditor, Upload) {
                var getImageFromClipboard, getImageFromDrop, getFileFromBase64;
                getImageFromClipboard = function (data) {
                    var i, item;
                    i = 0;
                    while (i < data.clipboardData.items.length) {
                        item = data.clipboardData.items[i];
                        if (item.type.indexOf("image") !== -1) {
                            return item.getAsFile() || false;
                        }
                        i++;
                    }
                    return false;
                };
                getImageFromDrop = function (data) {
                    var i, item, images;
                    i = 0;
                    images = [];
                    while (i < data.dataTransfer.files.length) {
                        item = data.dataTransfer.files[i];
                        if (item.type.indexOf("image") !== -1) {
                            images.push(item);
                        }
                        i++;
                    }
                    return images;
                };
                getFileFromBase64 = function (data, url) {
                    var arr = data.split(','), mime = arr[0].match(/:(.*?);/)[1],
                        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
                    while (n--) {
                        u8arr[n] = bstr.charCodeAt(n);
                    }
                    var filename, suffix;
                    if (typeof url != 'undefined') {
                        var urlArr = url.split('.');
                        filename = url.substr(url.lastIndexOf('/') + 1);
                        suffix = urlArr.pop();
                    } else {
                        filename = Math.random().toString(36).substring(5, 15);
                    }
                    if (!suffix) {
                        suffix = data.substring("data:image/".length, data.indexOf(";base64"));
                    }

                    var exp = new RegExp("\\." + suffix + "$", "i");
                    filename = exp.test(filename) ? filename : filename + "." + suffix;
                    var file = new File([u8arr], filename, {type: mime});
                    return file;
                };

                var getImageFromUrl = function (url, callback, outputFormat) {
                    var canvas = document.createElement('CANVAS'),
                        ctx = canvas.getContext('2d'),
                        img = new Image;
                    img.crossOrigin = 'Anonymous';
                    img.onload = function () {
                        var urlArr = url.split('.');
                        var suffix = urlArr.pop();
                        suffix = suffix.match(/^(jpg|png|gif|bmp|jpeg)$/i) ? suffix : 'png';

                        try {
                            canvas.height = img.height;
                            canvas.width = img.width;
                            ctx.drawImage(img, 0, 0);
                            var dataURL = canvas.toDataURL(outputFormat || 'image/' + suffix);
                            var file = getFileFromBase64(dataURL, url);
                        } catch (e) {
                            callback.call(this, null);
                        }

                        callback.call(this, file);
                        canvas = null;
                    };
                    img.onerror = function (e) {
                        callback.call(this, null);
                    };
                    img.src = Fast.api.fixurl("/addons/nkeditor/index/download") + "?url=" + encodeURIComponent(url);
                };
                //上传Word图片
                Nkeditor.uploadwordimage = function (index, image) {
                    var that = this;
                    (function () {
                        var file = getFileFromBase64(image);
                        var placeholder = new RegExp("##" + index + "##", "g");
                        Upload.api.send(file, function (data) {
                            that.html(that.html().replace(placeholder, Fast.api.cdnurl(data.url)));
                        }, function (data) {
                            that.html(that.html().replace(placeholder, ""));
                        });
                    }(index, image));
                };

                Nkeditor.lang({
                    remoteimage: '下载远程图片'
                });
                //远程下载图片
                Nkeditor.plugin('remoteimage', function (K) {
                    var editor = this, name = 'remoteimage';
                    editor.plugin.remoteimage = {
                        download: function (e) {
                            var that = this;
                            var html = that.html();
                            var staging = {}, orgined = {}, index = 0, images = 0, completed = 0, failured = 0;
                            var checkrestore = function () {
                                if (completed + failured >= images) {
                                    $.each(staging, function (i, j) {
                                        that.html(that.html().replace("<code>" + i + "</code>", j));
                                    });
                                }
                            };
                            html.replace(/<code>([\s\S]*?)<\/code>/g, function (code) {
                                    staging[index] = code;
                                    return "<code>" + index + "</code>";
                                }
                            );
                            html = html.replace(/<img([\s\S]*?)\ssrc\s*=\s*('|")((http(s?):)([\s\S]*?))('|")([\s\S]*?)[\/]?>/g, function () {
                                images++;
                                var url = arguments[3];
                                var placeholder = '<img src="' + Fast.api.cdnurl("/assets/addons/nkeditor/img/downloading.png") + '" data-index="' + index + '" />';
                                //如果是云存储的链接,则忽略
                                if (Config.upload.cdnurl && url.indexOf(Config.upload.cdnurl) > -1) {
                                    completed++;
                                    return arguments[0];
                                } else {
                                    orgined[index] = arguments[0];
                                }
                                //下载远程图片
                                (function (index, url, placeholder) {
                                    getImageFromUrl(url, function (file) {
                                        if (!file) {
                                            failured++;
                                            that.html(that.html().replace(placeholder, orgined[index]));
                                            checkrestore();
                                        } else {
                                            Upload.api.send(file, function (data) {
                                                completed++;
                                                that.html(that.html().replace(placeholder, '<img src="' + Fast.api.cdnurl(data.url) + '" />'));
                                                checkrestore();
                                            }, function (data) {
                                                failured++;
                                                that.html(that.html().replace(placeholder, orgined[index]));
                                                checkrestore();
                                            });
                                        }
                                    });
                                })(index, url, placeholder);
                                index++;
                                return placeholder;
                            });
                            if (index > 0) {
                                that.html(html);
                            } else {
                                Toastr.info("没有需要下载的远程图片");
                            }
                        }
                    };
                    // 点击图标时执行
                    editor.clickToolbar(name, editor.plugin.remoteimage.download);
                });

                $(".editor", form).each(function () {
                    var that = this;
                    Nkeditor.create(that, {
                        width: '100%',
                        filterMode: false,
                        wellFormatMode: false,
                        allowMediaUpload: true, //是否允许媒体上传
                        allowFileManager: true,
                        allowImageUpload: true,
                        fontSizeTable: ['9px', '10px', '12px', '14px', '16px', '18px', '21px', '24px', '32px'],
                        wordImageServer: typeof Config.nkeditor != 'undefined' && Config.nkeditor.wordimageserver ? "127.0.0.1:10101" : "", //word图片替换服务器的IP和端口
                        cssPath: Fast.api.cdnurl('/assets/addons/nkeditor/plugins/code/prism.css'),
                        cssData: "body {font-size: 13px}",
                        fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                        themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                        fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                        items: [
                            'source', 'undo', 'redo', 'preview', 'print', 'template', 'code', 'quote', 'cut', 'copy', 'paste',
                            'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
                            'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
                            'superscript', 'clearhtml', 'quickformat', 'selectall',
                            'formatblock', 'fontname', 'fontsize', 'forecolor', 'hilitecolor', 'bold',
                            'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', 'image', 'multiimage', 'graft',
                            'flash', 'media', 'insertfile', 'table', 'hr', 'emoticons', 'baidumap', 'pagebreak',
                            'anchor', 'link', 'unlink', 'remoteimage', 'about', 'fullscreen'
                        ],
                        afterCreate: function () {
                            var self = this;
                            //Ctrl+回车提交
                            Nkeditor.ctrl(document, 13, function () {
                                self.sync();
                                $(that).closest("form").submit();
                            });
                            Nkeditor.ctrl(self.edit.doc, 13, function () {
                                self.sync();
                                $(that).closest("form").submit();
                            });
                            //粘贴上传
                            $("body", self.edit.doc).bind('paste', function (event) {
                                var image, pasteEvent;
                                pasteEvent = event.originalEvent;
                                if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                                    image = getImageFromClipboard(pasteEvent);
                                    if (image) {
                                        event.preventDefault();
                                        Upload.api.send(image, function (data) {
                                            self.exec("insertimage", Fast.api.cdnurl(data.url));
                                        });
                                    }
                                }
                            });
                            //挺拽上传
                            $("body", self.edit.doc).bind('drop', function (event) {
                                var image, pasteEvent;
                                pasteEvent = event.originalEvent;
                                if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                                    images = getImageFromDrop(pasteEvent);
                                    if (images.length > 0) {
                                        event.preventDefault();
                                        $.each(images, function (i, image) {
                                            Upload.api.send(image, function (data) {
                                                self.exec("insertimage", Fast.api.cdnurl(data.url));
                                            });
                                        });
                                    }
                                }
                            });
                        },
                        //FastAdmin自定义处理
                        beforeUpload: function (callback, file) {
                            var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                            Upload.api.send(file, function (data) {
                                var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                                callback(data);
                            });

                        },
                        //错误处理 handler
                        errorMsgHandler: function (message, type) {
                            try {
                                console.log(message, type);
                            } catch (Error) {
                                alert(message);
                            }
                        }
                    });
                });
            });
        }
    }
});

require.config({
    paths: {
        'jquery-colorpicker': '../addons/wanlshop/js/jquery.colorpicker.min',
        'jquery-autocomplete': '../addons/wanlshop/js/jquery.autocomplete',
        'jquery-jqprint': '../addons/wanlshop/js/jquery.jqprint-0.3.min',
		'jquery-migrate': '../addons/wanlshop/js/jquery.migrate-1.2.1.min',
		'vue': '../addons/wanlshop/js/vue.min',
		'chat': '../addons/wanlshop/js/vue.min',
		'sortablejs': '../addons/wanlshop/js/Sortable.min',
		'vuedraggable': '../addons/wanlshop/js/vuedraggable.umd.min',
    },
    shim: {
        'jquery-colorpicker': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
        'jquery-autocomplete': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
		'jquery-jqprint': {
		    deps: ['jquery'],
		    exports: '$.fn.extend'
		},
		'jquery-migrate': {
		    deps: ['jquery'],
		    exports: '$.fn.extend'
		},
        'vue': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
		'chat': {
		    deps: ['css!../addons/wanlshop/css/chat.css'],
		    exports: '$.fn.extend'
		},
        'sortablejs': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
        'vuedraggable': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        }
    }
});
// 后台全局添加 IM即时通讯
if (Config.modulename == 'admin' && Config.controllername == 'index' && Config.actionname == 'index') {
	require(['chat'], function(Vue){
		var html = `<!-- 加载WanlChat 即时通讯 --><div class="wanl-chat-service" id="wanl-chat" v-cloak><!-- 消息提示 --><div class="wanl-chat-mini-msg" v-if="isMsg"><span>{{msgData.name}}：</span> {{msgData.text}}</div><!-- 全局按钮 --><div :style="{backgroundImage: 'url('+(isMsg ? msgData.avatar : '/assets/addons/wanlshop/img/common/chat_mini.png')+')'}" class="wanl-chat-mini-button" @click="onList" v-if="isList"><span class="label label-success" v-if="count > 0" v-cloak>{{count}}</span></div><!-- IM 右侧列表 --><div class="wanl-chat-list" v-else><div class="head"><div class="title"><div><h3>客服：{{service.nickname}}</h3><span v-if="shopOnline == 1"><i class="fa fa-circle text-success margin-r-5"></i> H5在线</span><span v-else><i class="fa fa-circle text-gray margin-r-5"></i> IM连接异常</span></div><div style="font-size: 14px;"><span class="active" @click="onAudio" v-if="isAudio"><i class="fa fa-volume-up text-red"></i></span><span v-else @click="onAudio"><i class="fa fa-volume-off link-black"></i></span><span style="margin-left: 10px; font-size: 16px;" @click="onList"><i class="fa fa-close link-black"></i></span></div></div></div><div class="list"><div class="empty" v-if="chatlist.length == 0"><div class="main"><img :src="cdnurl('/assets/addons/wanlshop/img/default/find_default3x.png')" ><p>没有找到任何联系人</p></div></div><div class="item" v-for="(item, index) in chatlist" :key="index" @click="otChat(index, 'main')"><div class="portrait"><img :src="cdnurl(item.avatar)"><span class="online"><i class="fa fa-circle text-success" v-if="item.isOnline == 1"></i><i class="fa fa-circle text-gray" v-else></i></span></div><div class="main"><div class="user"><span class="username text-cut">{{item.nickname}}</span><span class="time">{{timefriendly(item.createtime)}}</span></div><div class="info text-cut"><span v-if="item.count > 0">[未读{{item.count}}条]</span><span v-html="item.content"></span></div></div></div></div></div><!-- 聊天窗口 --><div class="wanl-chat" :class="{full: onFull}" :style="{left:screenWidth+'px', top:screenHeight+'px',}" ref="moveBtn" v-show="chatWindow" v-cloak><div class="list"><ul><li v-for="(item, index) in wanlchat" :key="index" :class="{checked: chatSelect == index}" @click="onChat(index)"><div class="portrait"><img :src="cdnurl(item.avatar)"><span class="badge bg-red" v-if="item.count > 0">{{item.count}}</span></div><div class="user-msg"><p>{{item.nickname}}</p><div class="text-cut" v-html="item.content"></div></div><div class="list-close" @click.stop="delChat(index)"><div class="hover"><span class="fa fa-times-circle"></span></div></div></li></ul></div><div class="main" v-if="chatSelect != null"><div class="msgHead" @mousedown="down" @touchstart="down" @mousemove="move" @touchmove="move" @mouseup="end" @touchend="end" @touchcancel="end"><img :src="cdnurl(wanlchat[chatSelect].avatar)"><div><span class="name">{{wanlchat[chatSelect].nickname}}</span><p v-if="wanlchat[chatSelect].isOnline == 1"><i class="fa fa-circle text-success"></i> 在线</p><p v-else><i class="fa fa-circle text-gray"></i> 离线</p></div><!-- 窗口操作 --><span class="layui-layer-setwin"><block v-if="onFull"><a class="layui-layer-ico layui-layer-max layui-layer-maxmin" href="javascript:;" @click="full"></a></block><block v-else><a class="layui-layer-min" href="javascript:;" @click="miniChat"><cite></cite></a><a class="layui-layer-ico layui-layer-max" href="javascript:;" @click="full"></a></block><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;" @click="closeChat"></a></span></div><div class="msgList" id="talk"><ul><li :class="{my: item.form.id == service.id}" v-for="(item, index) in chatContent" :key="index"><div class="chat-user"><img :src="cdnurl(item.form.id == service.id ? service.avatar : item.form.avatar)"><cite><span>{{timefriendly(item.createtime)}}</span></cite></div><!-- 文字消息 --><div class="chat-text" v-if="item.message.type == 'text'" v-html="item.message.content.text"></div><!-- 语音消息 --><div class="chat-voice" v-if="item.message.type == 'voice'" @click="playVoice(item.message.content.url)"><span :style="{marginRight: item.message.content.length * 8 +'px'}"></span>{{item.message.content.length}} ”</div><!-- 图片消息 --><div class="chat-img" v-if="item.message.type == 'img'"><a :href="item.message.content.url" target="_blank"><img :src="cdnurl(item.message.content.url)" data-tips-image></a></div></li></ul></div><form class="inputBox" id="form"><div class="tool"><span class="fa fa-smile-o" @click="toggleBox"></span><label for="upImage" class="fa fa-picture-o upImage"></label><input type="file" id="upImage" @change="chatImage" style="display:none"></div><div class="input"><textarea id="content" placeholder="请输入消息" v-model="textarea" @keyup.ctrl.enter="submit" autofocus></textarea></div><div class="operation"><button type="button" class="btn btn-danger" @click="submit">发送 Ctrl+Enter</button></div></form><div class="box-container" v-if="showBox" @click.self="toggleBox"> </div><div class="wanl-emoji" v-if="showBox"><div class="title"><div> {{TabCur}} </div></div><div class="subject" v-for="(emoji, groups) in emojiList.groups" :key="groups" v-if="TabCur == groups"><div class="item"><span v-for="(item, index) in emoji" :key="index" @click="addEmoji(item.value)"><img :src="item.url" ></span></div></div><div class="emojiNav"><div :class="item == TabCur ? 'emojibg' : ''" class="item" v-for="(item, index) in emojiList.categories" :key="index" :data-id="item" @click="tabSelect" ><img :src="emojiList.groups[item][0].url" ></div></div></div></div></div></div>`;
		$("body").append(html);
		var wanlchat =  new Vue({
			el:"#wanl-chat",
			data:{
				count: 0, // 未读总数
				chatlist: [], // 主列表
				chatWindow: false, // 是否开启聊天窗口
				isList: true, // 展示列表或按钮 --
				isMsg: false, // 是否开启消息弹窗 -- 
				msgData: {
					avatar: '',
					name: '',
					text: ''
				}, // 信息内容 -- 
				chatMiniWindow: false, //最小化窗口
				wanlchat: [], // 聊天窗口列表
				chatSelect: null, // 选中的记录
				chatContent: [], //消息内容&历史记录
				textarea: '', // 编辑框
				shopOnline: 1, // 商家在线状态
				isAudio: true, // 消息提示
				service: {
					nickname: 'IM加载中..'
				}, 
				// 表情
				emojiList: [],
				TabCur: '默认',
				showBox: false,
				// 操作窗口
				screenWidth: (document.body.clientWidth - 800) / 2,
				screenHeight: (document.body.clientHeight - 600) / 2,
				flags: false,
				position: {
					x: 0,
					y: 0
				},
				nx: '',
				ny: '',
				dx: '',
				dy: '',
				xPum: '',
				yPum: '',
				isShow: false,
				moveBtn: {},
				onFull: false
			},
			mounted() {
				this.moveBtn = this.$refs.moveBtn;
				// 获取列表
				this.loadData();
				// 表情数据
				this.emojiList = this.emojiData();
			},
			methods: {
				loadData() {
					let app = this;
					Fast.api.ajax({
						url: "wanlshop/service/lists.html",
					}, (data, ret) => {
						app.chatlist = data.chat;
						app.service = data.service;
						// 统计总数
						app.chatCount();
						// 开启即时通讯
						// 连接IM服务器
						const ws = new WebSocket(data.service.socketurl);
						let sendTimmer = null;
						let sendCount = 0;
						ws.onopen = ()=> {
							console.log('IM 启动成功');
							// sendCount++;
							// ws.send('Hello Server!' + sendCount);
							// sendTimmer = setInterval(function () {
							// 	sendCount++;
							// 	ws.send('Hi Server!' + sendCount);
							// 	if (sendCount === 10) {
							// 		ws.close();
							// 	}
							// }, 2000);
						};
						ws.onmessage = (msg)=> {
							let data = JSON.parse(msg.data);
							if (data.type == 'init') {
								console.log('@message_client_id：' + data.client_id);
								Fast.api.ajax({
									url: "wanlshop/service/bind.html",
									data: {client_id: data.client_id}
								}, function(data, ret){
									app.shopOnline = data;
									return false;
								}, function(data, ret){
									return false;
								});
							}else if (data.type == 'ping') {
								ws.send('{"type":"pong"}');
							}else if (data.type == 'service'){
								// 更新类型
								let updateType = null;
								//判断是否开启窗口
								if(this.chatWindow){
									// 判断是否当前用户
									if (data.form.id == this.wanlchat[this.chatSelect].user_id) {
										// 更新当前页面消息
										this.receiveChat(data);
										updateType = 'openinto';
									}else{
										updateType = 'open';
									}
								}else{
									updateType = 'main';
								}
								// 全局消息提示
								this.onMsg(data, updateType);
								// 更新主列表和 wanlchat列表，如果存在+1，如果不存在追加一个列表
								this.updateChatList(data, updateType);
							}
						};
						ws.onclose = ()=> {
							console.log('IM 已关闭');
							// sendTimmer && clearInterval(sendTimmer);
						};
						ws.onerror = ()=> {
							console.log('IM 错误');
						};
						return false;
					});
				},
				// 发送到服务器
				send(data) {
					Fast.api.ajax({
						url: "wanlshop/service/send.html",
						data: data
					}, function(data, ret){
						return false;
					});
				},
				onList(){
					// 如果窗口最小化
					if(this.chatMiniWindow){
						this.chatMiniWindow = false;
						this.chatWindow = true;
						this.isList = !this.isList;
					}else{
						this.isList = !this.isList;
					}
				},
				// 打开主列表中
				otChat(data, type){
					let chat = type == 'main' ? this.chatlist[data] : {
						user_id: data.form.id,
						nickname: data.form.name,
						avatar: data.form.avatar,
						isOnline: 1,
						count: 0,
						content: this.typeMsg(data)
					};
					// 打开子窗口
					this.onChat(this.addWanlChatList(chat));
					// 判断是否最小化，如果最小化关闭，打开窗口
					if(this.chatMiniWindow){
						this.chatMiniWindow = false;
						this.chatWindow = true;
					}else{
						this.chatWindow = true;
					}
					this.isList = !this.isList;
				},
				// 子窗口点击
				onChat(index){
					this.chatSelect = index;
					let chat = this.wanlchat[index];
					let app = this;
					Fast.api.ajax({
						url: "wanlshop/service/history.html",
						data: {
							id: chat.user_id
						}
					}, function(data, ret) {
						// 替换表情
						data.chat.forEach((item) => {
							if (item.message.type == 'text') {
								item.message.content.text = app.replaceEmoji(item.message.content.text);
							}
						})
						// 写入记录
						app.chatContent = data.chat;
						// 更新在线状态
						chat.isOnline = data.isOnline;
						// 更新数据
						app.count -= chat.count;
						chat.count = 0;
						// 更新主列表
						app.chatlist[app.addChatList(chat, 'fun')].count = 0;
						// 滚动最底部
						app.latest();
						return false;
					});
				},
				// 更新主列表和 wanlchat列表，如果存在+1，如果不存在追加一个列表
				updateChatList(chat, type){
					let content = this.typeMsg(chat);
					if(type == 'send'){
						this.wanlchat[this.chatSelect].content = content;
						this.chatlist.forEach((item, index) => {
							if(item.user_id == chat.to_id){
								item.content = content;
							}
						});
					}else{
						let chatlist = this.chatlist[this.addChatList(chat, 'msg')];
						let wanlchat = this.wanlchat[this.addWanlChatList(chat, 'msg')];
						// 更新在线状态
						chatlist.isOnline = 1;
						wanlchat.isOnline = 1;
						// 已开窗口，确定此消息，更改已读
						if(type == 'openinto'){
							// 更新主列表
							chatlist.content = content;
							// 更新子列表
							wanlchat.content = content;
							// 设置已读
							Fast.api.ajax({
								url: "wanlshop/service/read.html",
								data: {
									id: wanlchat.user_id
								}
							}, function(data, ret) {
								return false;
							});
						}else if(type == 'open'){
							// 更新主列表
							chatlist.content = content;
							chatlist.count += 1;
							this.count += 1;
							// 更新子列表
							wanlchat.content = content;
							wanlchat.count += 1;
						}else if(type == 'main'){
							// 更新主列表
							chatlist.content = content;
							chatlist.count += 1;
							this.count += 1;
						}
					}
				},
				// 判断主列表是否存在,不存在新增
				addChatList(chat, type){
					let data = type == 'msg' ? {
						user_id: chat.form.id,
						nickname: chat.form.name,
						avatar: chat.form.avatar,
						content: this.typeMsg(chat),
						isOnline: 1,
						createtime: chat.createtime
					}:{
						user_id: chat.user_id,
						nickname: chat.nickname,
						avatar: chat.avatar,
						content: "没有任何消息",
						isOnline: chat.isOnline,
						createtime: (Date.parse( new Date() ).toString()).substr(0,10)
					};
					let chatlist = this.chatlist;
					let key = null;
					chatlist.forEach((item, index) => {
						if(item.user_id == data.user_id){
							key = index;
						}
					});
					if(key == null){
						chatlist.push(data);
						key = chatlist.length-1;
					}
					return key;
				},
				// 判断wanlshop列表是否存在
				addWanlChatList(data, type){
					let chat = {};
					if(type == 'msg'){
						chat = {
							user_id: data.form.id,
							nickname: data.form.name,
							avatar: data.form.avatar,
							isOnline: 1,
							content: this.typeMsg(data)
						};
					}else{
						chat = data;
					}
					let wanlchat = this.wanlchat;
					let key = null;
					wanlchat.forEach((item, index) => {
						if(item.user_id == chat.user_id){
							key = index;
						}
					});
					if(key == null){
						wanlchat.push({
							user_id: chat.user_id,
							nickname: chat.nickname,
							avatar: chat.avatar,
							isOnline: chat.isOnline,
							count: chat.count,
							content: chat.content
						});
						key = wanlchat.length-1;
					}
					return key;
				},
				// 删除窗口，如果只有一个则直接关闭 判断删除的是否当前的，如果是读取第一个，如果不是直接删掉即可
				delChat(index){
					if(this.wanlchat.length == 1){
						this.closeChat();
					}else{
						// 删除指定键
						Vue.delete(this.wanlchat, index);
						// 重新读取页面
						this.onChat(this.wanlchat.length-1);
					}
				},
				// 关闭窗口
				closeChat(){
					this.chatWindow = false; // 是否开启聊天窗口
					this.wanlchat =  []; // 聊天窗口列表
					this.chatSelect =  null; // 选中的记录
					this.chatContent = []; //消息内容&历史记录
				},
				// 最小化窗口
				miniChat(){
					this.chatWindow = !this.chatWindow; // 聊天窗口
					this.chatMiniWindow = !this.chatMiniWindow; // 是否开启聊天窗口
				},
				// 点击文本消息
				submit() {
					if (!this.textarea) {
						return;
					}
					var msg = {
						text: this.textarea
					};
					this.sendMsg(msg, 'text');
					this.textarea = ''; //清空输入框
				},
				// 发送图片消息
				chatImage(e){
					var files = e.target.files[0];
					var formData = new FormData();
					var app = this;
					formData.append('file', files, files.name);
					Fast.api.ajax({
					    url: "ajax/upload", 
						data:formData,
						processData:false,
						contentType:false,
					}, function(data, ret){
						var theImage = new Image(); 
						theImage.src = data.fullurl; 
						var msg = {
							h: theImage.height,
							w: theImage.width,
							url: data.fullurl
						};
						app.sendMsg(msg, 'img');
						return false;
					});
				},
				// 发送消息
				sendMsg(content, type) {
					var data = {
						type: 'service',
						to_id: this.wanlchat[this.chatSelect].user_id,
						form: {
							id: this.service.id,
							avatar: this.service.avatar,
							name: this.service.nickname
						},
						message: {
							type: type,
							content: content
						},
						createtime: parseInt(new Date().getTime() / 1000)
					};
					// 发送到本地
					this.receiveChat(JSON.parse(JSON.stringify(data)));
					// 发送消息
					this.send(data);
					// 更新主列表和 wanlchat列表，如果存在+1，如果不存在追加一个列表
					this.updateChatList(data, 'send');
				},
				// 接受消息
				receiveChat(msg) {
					if (msg.type == 'service') {
						if (msg.message.type == 'text') {
							msg.message.content.text = this.replaceEmoji(msg.message.content.text);
						}
						this.chatContent.push(msg);
					}
					// 滚动到底
					this.latest();
				},
				// 播放语音
				playVoice(url) {
					let sound = new Audio();
					sound.src = url;
					sound.play();
				},
				//统计数量
				chatCount(){
					let count = 0;
					this.chatlist.forEach((item)=>{  
					   count += item.count;
					});
					this.count = count;
				},
				// 消息提示
				onMsg(msg, type){
					let text = '';
					// 文本提示
					if(type == 'main'){
						text = `新消息：${msg.form.name}，${this.typeMsg(msg)}`;
						this.msgData = {
							avatar: this.cdnurl(msg.form.avatar),
							name: msg.form.name,
							text: this.typeMsg(msg)
						};
						this.openMsg();
					}
					// 语音提示
					if(this.isAudio){
						this.playAudio(type, text);
					}
				},
				//打开消息弹窗
				openMsg(){
					this.isMsg = true;
					setInterval (()=> {
					    this.isMsg = false;
					}, 5000);
				},
				// 在线语音合成
				playAudio(type, str){
					let sound = new Audio();
					let url = '';
					if(type == 'main'){
						url = str ? ('https://tts.baidu.com/text2audio?lan=zh&ie=UTF-8&spd=6&text=' + encodeURI(str)):'';
					}else if(type == 'openinto'){
						url = this.cdnurl('/assets/addons/wanlshop/voice/open.mp3');
					}else if(type == 'open'){
						url = this.cdnurl('/assets/addons/wanlshop/voice/chat.mp3');
					}
				    sound.src = url;
				    sound.play();
				},
				onAudio(){
					this.isAudio = !this.isAudio;
					this.isAudio ? layer.msg('提示音已开启', {icon: 1}):layer.msg('提示音已关闭', {icon: 2});
				},
				typeMsg(msg){
					let text = '';
					if (msg.type == 'system') {
						if (msg.msg.type == 'text') {
							text = msg.message.content.text;
						}
					} else if (msg.type == 'service') {
						// 用户消息
						if (msg.message.type == 'text') {
							text = msg.message.content.text;
						}else if (msg.message.type == 'voice') {
							text = '[语音消息]';
						}else if (msg.message.type == 'img') {
							text = '[图片消息]';
						}else if (msg.message.type == 'goods') {
							text = '[商品消息]';
						}else if (msg.message.type == 'order') {
							text = '[订单消息]';
						}else{
							text = '[未知类型消息]';
						}
					}
					return text;
				},
				//替换表情符号为图片
				replaceEmoji(text) {
					// 这里处理 链接   换行符
					let replacedStr = text.replace(/\[([^(\]|\[)]*)\]/g, (item, index) => {
						return '<img src="' + this.emojiList.map[item] + '" width="18rpx">';
					});
					return replacedStr.replace(/(\r\n)|(\n)/g, '<br>');
				},
				// 表情tab
				tabSelect(e) {
					this.TabCur = e.currentTarget.dataset.id;
				},
				//添加表情
				addEmoji(em) {
					this.textarea += em;
					this.toggleBox();
				},
				// 点击空白区域关闭某个div图层
				toggleBox() {
					this.showBox = !this.showBox;  //通过控制showBox来控制box的显示与隐藏
				},
				// 滚动底部
				latest(){
					if(this.chatWindow){
						this.$nextTick(() => {
							let msg = document.getElementById('talk') // 获取对象
							msg.scrollTop = msg.scrollHeight // 滚动高度
						})
					}
				},
				cdnurl(url) {
					return Fast.api.cdnurl(url);
				},
				toFind(type){
					var name = '发布';
					if(type == 'new'){
						name = '发布 上新'
					}else if(type == 'want'){
						name = '发布 种草'
					}else if(type == 'show'){
						name = '发布 买家秀'
					}
					Fast.api.open('/index/wanlshop.find/add.html?type='+type, name);
				},
				full(){
					this.onFull = !this.onFull;
				},
				// 实现移动端拖拽
				down() {
					this.flags = true;
					var touch;
					if (event.touches) {
						touch = event.touches[0];
					} else {
						touch = event;
					}
					this.position.x = touch.clientX;
					this.position.y = touch.clientY;
					this.dx = this.moveBtn.offsetLeft;
					this.dy = this.moveBtn.offsetTop;
				},
				move() {
					if (this.flags) {
						var touch;
						if (event.touches) {
							touch = event.touches[0];
						} else {
							touch = event;
						}
						this.nx = touch.clientX - this.position.x;
						this.ny = touch.clientY - this.position.y;
						this.xPum = this.dx + this.nx;
						this.yPum = this.dy + this.ny;
						var clientWidth = document.documentElement.clientWidth;
						var clientHeight = document.documentElement.clientHeight;
						if (this.xPum > 0 && this.xPum < (clientWidth - this.moveBtn.offsetWidth)) {
							this.moveBtn.style.left = this.xPum + "px";
						}
						if (this.yPum > 0 && this.yPum < (clientHeight - this.moveBtn.offsetHeight)) {
							this.moveBtn.style.top = this.yPum + "px";
						}
									
						//阻止页面的滑动默认事件
						document.addEventListener("touchmove", this.handler, {
							passive: false
						});
					}
				},
				//鼠标释放时候的函数
				end() {
					this.flags = false;
					document.addEventListener('touchmove', this.handler, {
						passive: false
					});
				},
				handler(e) {
					if(this.flags){
						event.preventDefault(); 
					}else{
						return true
					}
				},
				timeFormat(timestamp = null, fmt = 'yyyy-mm-dd'){
					// yyyy:mm:dd|yyyy:mm|yyyy年mm月dd日|yyyy年mm月dd日 hh时MM分等,可自定义组合
					timestamp = parseInt(timestamp);
					// 如果为null,则格式化当前时间
					if (!timestamp) timestamp = Number(new Date());
					// 判断用户输入的时间戳是秒还是毫秒,一般前端js获取的时间戳是毫秒(13位),后端传过来的为秒(10位)
					if (timestamp.toString().length == 10) timestamp *= 1000;
					let date = new Date(timestamp);
					let ret;
					let opt = {
						"y+": date.getFullYear().toString(), // 年
						"m+": (date.getMonth() + 1).toString(), // 月
						"d+": date.getDate().toString(), // 日
						"h+": date.getHours().toString(), // 时
						"M+": date.getMinutes().toString(), // 分
						"s+": date.getSeconds().toString() // 秒
						// 有其他格式化字符需求可以继续添加，必须转化成字符串
					};
					for (let k in opt) {
						ret = new RegExp("(" + k + ")").exec(fmt);
						if (ret) {
							fmt = fmt.replace(ret[1], (ret[1].length == 1) ? (opt[k]) : (opt[k].padStart(ret[1].length, "0")))
						};
					};
					return fmt;
				},
				timefriendly(timestamp){
					if (timestamp == null) timestamp = Number(new Date());
					timestamp = parseInt(timestamp);
					// 判断用户输入的时间戳是秒还是毫秒,一般前端js获取的时间戳是毫秒(13位),后端传过来的为秒(10位)
					if (timestamp.toString().length == 10) timestamp *= 1000;
					var timer = (new Date()).getTime() - timestamp;
					timer = parseInt(timer / 1000);
					// 如果小于5分钟,则返回"刚刚",其他以此类推
					let tips = '';
					switch (true) {
						case timer < 86400:
							tips = this.timeFormat(timestamp, 'hh:MM');
							break;
						case timer >= 86400 && timer < 86400 * 7:
							var now = new Date(timestamp);
							var week = ['日', '一', '二', '三', '四', '五', '六'];
							switch (new Date().getDate() - now.getDate()) {
								case 1:
									tips = this.timeFormat(timestamp, '昨天 hh:MM');
									break;
								case 2:
									tips = this.timeFormat(timestamp, '前天 hh:MM');
									break;
								default:
									tips = '星期' + week[now.getDay()] + this.timeFormat(timestamp, 'hh:MM');
							}
							break;
						case timer >= 86400 * 7:
							tips = this.timeFormat(timestamp, 'mm-dd hh:MM');
							break;
						default:
							tips = this.timeFormat(timestamp, 'yyyy-mm-dd hh:MM');
					}
					return tips;
				},
				// 表情数据
				emojiData() {
					let emotions = [{"phrase": "[微笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e3/2018new_weixioa02_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e3/2018new_weixioa02_org.png","value": "[微笑]","picid": ""}, {"phrase": "[可爱]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/09/2018new_keai_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/09/2018new_keai_org.png","value": "[可爱]","picid": ""}, {"phrase": "[太开心]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_taikaixin_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_taikaixin_org.png","value": "[太开心]","picid": ""}, {"phrase": "[鼓掌]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6e/2018new_guzhang_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6e/2018new_guzhang_thumb.png","value": "[鼓掌]","picid": ""}, {"phrase": "[嘻嘻]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/33/2018new_xixi_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/33/2018new_xixi_thumb.png","value": "[嘻嘻]","picid": ""}, {"phrase": "[哈哈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8f/2018new_haha_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8f/2018new_haha_thumb.png","value": "[哈哈]","picid": ""}, {"phrase": "[笑cry]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4a/2018new_xiaoku_thumb.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4a/2018new_xiaoku_thumb.png","value": "[笑cry]","picid": ""}, {"phrase": "[挤眼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/43/2018new_jiyan_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/43/2018new_jiyan_org.png","value": "[挤眼]","picid": ""}, {"phrase": "[馋嘴]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fa/2018new_chanzui_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fa/2018new_chanzui_org.png","value": "[馋嘴]","picid": ""}, {"phrase": "[黑线]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a3/2018new_heixian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a3/2018new_heixian_thumb.png","value": "[黑线]","picid": ""}, {"phrase": "[汗]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/28/2018new_han_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/28/2018new_han_org.png","value": "[汗]","picid": ""}, {"phrase": "[挖鼻]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9a/2018new_wabi_thumb.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9a/2018new_wabi_thumb.png","value": "[挖鼻]","picid": ""}, {"phrase": "[哼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7c/2018new_heng_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7c/2018new_heng_thumb.png","value": "[哼]","picid": ""}, {"phrase": "[怒]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_nu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_nu_thumb.png","value": "[怒]","picid": ""}, {"phrase": "[委屈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a5/2018new_weiqu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a5/2018new_weiqu_thumb.png","value": "[委屈]","picid": ""}, {"phrase": "[可怜]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/96/2018new_kelian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/96/2018new_kelian_org.png","value": "[可怜]","picid": ""}, {"phrase": "[失望]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_shiwang_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_shiwang_thumb.png","value": "[失望]","picid": ""}, {"phrase": "[悲伤]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ee/2018new_beishang_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ee/2018new_beishang_org.png","value": "[悲伤]","picid": ""}, {"phrase": "[泪]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6e/2018new_leimu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6e/2018new_leimu_org.png","value": "[泪]","picid": ""}, {"phrase": "[允悲]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/83/2018new_kuxiao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/83/2018new_kuxiao_org.png","value": "[允悲]","picid": ""}, {"phrase": "[害羞]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c1/2018new_haixiu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c1/2018new_haixiu_org.png","value": "[害羞]","picid": ""}, {"phrase": "[污]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/10/2018new_wu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/10/2018new_wu_thumb.png","value": "[污]","picid": ""}, {"phrase": "[爱你]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_aini_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_aini_org.png","value": "[爱你]","picid": ""}, {"phrase": "[亲亲]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2c/2018new_qinqin_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2c/2018new_qinqin_thumb.png","value": "[亲亲]","picid": ""}, {"phrase": "[色]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9d/2018new_huaxin_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9d/2018new_huaxin_org.png","value": "[色]","picid": ""}, {"phrase": "[憧憬]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c9/2018new_chongjing_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c9/2018new_chongjing_org.png","value": "[憧憬]","picid": ""}, {"phrase": "[舔屏]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3e/2018new_tianping_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3e/2018new_tianping_thumb.png","value": "[舔屏]","picid": ""}, {"phrase": "[坏笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4d/2018new_huaixiao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4d/2018new_huaixiao_org.png","value": "[坏笑]","picid": ""}, {"phrase": "[阴险]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9e/2018new_yinxian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9e/2018new_yinxian_org.png","value": "[阴险]","picid": ""}, {"phrase": "[笑而不语]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2d/2018new_xiaoerbuyu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2d/2018new_xiaoerbuyu_org.png","value": "[笑而不语]","picid": ""}, {"phrase": "[偷笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/71/2018new_touxiao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/71/2018new_touxiao_org.png","value": "[偷笑]","picid": ""}, {"phrase": "[酷]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c4/2018new_ku_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c4/2018new_ku_org.png","value": "[酷]","picid": ""}, {"phrase": "[并不简单]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_bingbujiandan_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_bingbujiandan_thumb.png","value": "[并不简单]","picid": ""}, {"phrase": "[思考]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/30/2018new_sikao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/30/2018new_sikao_org.png","value": "[思考]","picid": ""}, {"phrase": "[疑问]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b8/2018new_ningwen_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b8/2018new_ningwen_org.png","value": "[疑问]","picid": ""}, {"phrase": "[费解]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2a/2018new_wenhao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/2a/2018new_wenhao_thumb.png","value": "[费解]","picid": ""}, {"phrase": "[晕]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/07/2018new_yun_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/07/2018new_yun_thumb.png","value": "[晕]","picid": ""}, {"phrase": "[衰]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a2/2018new_shuai_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a2/2018new_shuai_thumb.png","value": "[衰]","picid": ""}, {"phrase": "[骷髅]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_kulou_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_kulou_thumb.png","value": "[骷髅]","picid": ""}, {"phrase": "[嘘]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b0/2018new_xu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b0/2018new_xu_org.png","value": "[嘘]","picid": ""}, {"phrase": "[闭嘴]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/2018new_bizui_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/2018new_bizui_org.png","value": "[闭嘴]","picid": ""}, {"phrase": "[傻眼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/dd/2018new_shayan_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/dd/2018new_shayan_org.png","value": "[傻眼]","picid": ""}, {"phrase": "[吃惊]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/49/2018new_chijing_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/49/2018new_chijing_org.png","value": "[吃惊]","picid": ""}, {"phrase": "[吐]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/08/2018new_tu_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/08/2018new_tu_org.png","value": "[吐]","picid": ""}, {"phrase": "[感冒]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/40/2018new_kouzhao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/40/2018new_kouzhao_thumb.png","value": "[感冒]","picid": ""}, {"phrase": "[生病]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3b/2018new_shengbing_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3b/2018new_shengbing_thumb.png","value": "[生病]","picid": ""}, {"phrase": "[拜拜]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fd/2018new_baibai_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fd/2018new_baibai_thumb.png","value": "[拜拜]","picid": ""}, {"phrase": "[鄙视]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/da/2018new_bishi_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/da/2018new_bishi_org.png","value": "[鄙视]","picid": ""}, {"phrase": "[白眼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ef/2018new_landelini_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ef/2018new_landelini_org.png","value": "[白眼]","picid": ""}, {"phrase": "[左哼哼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/43/2018new_zuohengheng_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/43/2018new_zuohengheng_thumb.png","value": "[左哼哼]","picid": ""}, {"phrase": "[右哼哼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c1/2018new_youhengheng_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c1/2018new_youhengheng_thumb.png","value": "[右哼哼]","picid": ""}, {"phrase": "[抓狂]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/17/2018new_zhuakuang_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/17/2018new_zhuakuang_org.png","value": "[抓狂]","picid": ""}, {"phrase": "[怒骂]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/87/2018new_zhouma_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/87/2018new_zhouma_thumb.png","value": "[怒骂]","picid": ""}, {"phrase": "[打脸]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cb/2018new_dalian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cb/2018new_dalian_org.png","value": "[打脸]","picid": ""}, {"phrase": "[顶]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ae/2018new_ding_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ae/2018new_ding_org.png","value": "[顶]","picid": ""}, {"phrase": "[互粉]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/2018new_hufen02_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/2018new_hufen02_org.png","value": "[互粉]","picid": ""}, {"phrase": "[钱]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a2/2018new_qian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a2/2018new_qian_thumb.png","value": "[钱]","picid": ""}, {"phrase": "[哈欠]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/55/2018new_dahaqian_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/55/2018new_dahaqian_org.png","value": "[哈欠]","picid": ""}, {"phrase": "[困]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3c/2018new_kun_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3c/2018new_kun_thumb.png","value": "[困]","picid": ""}, {"phrase": "[睡]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/2018new_shuijiao_thumb.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/2018new_shuijiao_thumb.png","value": "[睡]","picid": ""}, {"phrase": "[吃瓜]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/01/2018new_chigua_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/01/2018new_chigua_thumb.png","value": "[吃瓜]","picid": ""}, {"phrase": "[doge]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_doge02_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_doge02_org.png","value": "[doge]","picid": ""}, {"phrase": "[二哈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/22/2018new_erha_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/22/2018new_erha_org.png","value": "[二哈]","picid": ""}, {"phrase": "[喵喵]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7b/2018new_miaomiao_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7b/2018new_miaomiao_thumb.png","value": "[喵喵]","picid": ""}, {"phrase": "[赞]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e6/2018new_zan_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e6/2018new_zan_org.png","value": "[赞]","picid": ""}, {"phrase": "[good]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8a/2018new_good_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8a/2018new_good_org.png","value": "[good]","picid": ""}, {"phrase": "[ok]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/45/2018new_ok_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/45/2018new_ok_org.png","value": "[ok]","picid": ""}, {"phrase": "[耶]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/29/2018new_ye_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/29/2018new_ye_thumb.png","value": "[耶]","picid": ""}, {"phrase": "[握手]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e9/2018new_woshou_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e9/2018new_woshou_thumb.png","value": "[握手]","picid": ""}, {"phrase": "[作揖]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e7/2018new_zuoyi_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e7/2018new_zuoyi_org.png","value": "[作揖]","picid": ""}, {"phrase": "[来]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/42/2018new_guolai_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/42/2018new_guolai_thumb.png","value": "[来]","picid": ""}, {"phrase": "[拳头]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/2018new_quantou_org.png","hot": false,"common": true,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/2018new_quantou_thumb.png","value": "[拳头]","picid": ""}, {"phrase": "[点亮橙色]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f3/gongyi_dianliangchengse_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f3/gongyi_dianliangchengse_thumb.png","value": "[点亮橙色]","picid": ""}, {"phrase": "[人人公益节]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/huodong_renrengongyi_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/huodong_renrengongyi_thumb.png","value": "[人人公益节]","picid": ""}, {"phrase": "[中国赞]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6d/2018new_zhongguozan_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6d/2018new_zhongguozan_org.png","value": "[中国赞]","picid": ""}, {"phrase": "[锦鲤]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/94/hbf2019_jinli_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/94/hbf2019_jinli_thumb.png","value": "[锦鲤]","picid": ""}, {"phrase": "[抱抱]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/42/2018new_baobao_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/42/2018new_baobao_thumb.png","value": "[抱抱]","picid": ""}, {"phrase": "[摊手]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/2018new_tanshou_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/2018new_tanshou_org.png","value": "[摊手]","picid": ""}, {"phrase": "[跪了]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/75/2018new_gui_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/75/2018new_gui_org.png","value": "[跪了]","picid": ""}, {"phrase": "[酸]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/hot_wosuanle_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/hot_wosuanle_thumb.png","value": "[酸]","picid": ""}, {"phrase": "[哪吒开心]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/nezha_kaixin02_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/nezha_kaixin02_thumb.png","value": "[哪吒开心]","picid": ""}, {"phrase": "[冰雪奇缘艾莎]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/11/bingxueqiyuan_aisha_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/11/bingxueqiyuan_aisha_thumb.png","value": "[冰雪奇缘艾莎]","picid": ""}, {"phrase": "[冰雪奇缘安娜]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/bingxueqiyuan_anna_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/bingxueqiyuan_anna_thumb.png","value": "[冰雪奇缘安娜]","picid": ""}, {"phrase": "[冰雪奇缘雪宝]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/96/bingxueqiyuan_xuebao_org.png","hot": true,"common": false,"category": "","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/96/bingxueqiyuan_xuebao_thumb.png","value": "[冰雪奇缘雪宝]","picid": ""}, {"phrase": "[心]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8a/2018new_xin_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8a/2018new_xin_thumb.png","value": "[心]","picid": ""}, {"phrase": "[伤心]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6c/2018new_xinsui_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6c/2018new_xinsui_thumb.png","value": "[伤心]","picid": ""}, {"phrase": "[鲜花]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d4/2018new_xianhua_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d4/2018new_xianhua_org.png","value": "[鲜花]","picid": ""}, {"phrase": "[男孩儿]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0a/2018new_nanhai_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0a/2018new_nanhai_thumb.png","value": "[男孩儿]","picid": ""}, {"phrase": "[女孩儿]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/39/2018new_nvhai_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/39/2018new_nvhai_thumb.png","value": "[女孩儿]","picid": ""}, {"phrase": "[熊猫]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_xiongmao_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_xiongmao_thumb.png","value": "[熊猫]","picid": ""}, {"phrase": "[兔子]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/2018new_tuzi_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/2018new_tuzi_thumb.png","value": "[兔子]","picid": ""}, {"phrase": "[猪头]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1c/2018new_zhutou_thumb.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1c/2018new_zhutou_thumb.png","value": "[猪头]","picid": ""}, {"phrase": "[草泥马]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3b/2018new_caonima_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3b/2018new_caonima_thumb.png","value": "[草泥马]","picid": ""}, {"phrase": "[奥特曼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/2018new_aoteman_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/2018new_aoteman_org.png","value": "[奥特曼]","picid": ""}, {"phrase": "[太阳]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cd/2018new_taiyang_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cd/2018new_taiyang_org.png","value": "[太阳]","picid": ""}, {"phrase": "[月亮]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d5/2018new_yueliang_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d5/2018new_yueliang_org.png","value": "[月亮]","picid": ""}, {"phrase": "[浮云]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/61/2018new_yunduo_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/61/2018new_yunduo_thumb.png","value": "[浮云]","picid": ""}, {"phrase": "[下雨]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7e/2018new_yu_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7e/2018new_yu_thumb.png","value": "[下雨]","picid": ""}, {"phrase": "[沙尘暴]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b7/2018new_shachenbao_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b7/2018new_shachenbao_org.png","value": "[沙尘暴]","picid": ""}, {"phrase": "[微风]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c7/2018new_weifeng_thumb.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c7/2018new_weifeng_thumb.png","value": "[微风]","picid": ""}, {"phrase": "[围观]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6c/2018new_weiguan_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/6c/2018new_weiguan_org.png","value": "[围观]","picid": ""}, {"phrase": "[飞机]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4a/2018new_feiji_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4a/2018new_feiji_thumb.png","value": "[飞机]","picid": ""}, {"phrase": "[照相机]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/78/2018new_xiangji_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/78/2018new_xiangji_thumb.png","value": "[照相机]","picid": ""}, {"phrase": "[话筒]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/48/2018new_huatong_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/48/2018new_huatong_org.png","value": "[话筒]","picid": ""}, {"phrase": "[蜡烛]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/16/2018new_lazhu_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/16/2018new_lazhu_org.png","value": "[蜡烛]","picid": ""}, {"phrase": "[音乐]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1f/2018new_yinyue_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1f/2018new_yinyue_org.png","value": "[音乐]","picid": ""}, {"phrase": "[喜]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e0/2018new_xizi_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e0/2018new_xizi_thumb.png","value": "[喜]","picid": ""}, {"phrase": "[给力]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/36/2018new_geili_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/36/2018new_geili_thumb.png","value": "[给力]","picid": ""}, {"phrase": "[威武]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/14/2018new_weiwu_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/14/2018new_weiwu_thumb.png","value": "[威武]","picid": ""}, {"phrase": "[干杯]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/40/2018new_ganbei_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/40/2018new_ganbei_org.png","value": "[干杯]","picid": ""}, {"phrase": "[蛋糕]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/2018new_dangao_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/2018new_dangao_org.png","value": "[蛋糕]","picid": ""}, {"phrase": "[礼物]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0e/2018new_liwu_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0e/2018new_liwu_org.png","value": "[礼物]","picid": ""}, {"phrase": "[钟]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8e/2018new_zhong_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/8e/2018new_zhong_org.png","value": "[钟]","picid": ""}, {"phrase": "[肥皂]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d6/2018new_feizao_thumb.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d6/2018new_feizao_thumb.png","value": "[肥皂]","picid": ""}, {"phrase": "[绿丝带]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cb/2018new_lvsidai_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cb/2018new_lvsidai_thumb.png","value": "[绿丝带]","picid": ""}, {"phrase": "[围脖]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/64/2018new_weibo_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/64/2018new_weibo_org.png","value": "[围脖]","picid": ""}, {"phrase": "[浪]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/46/2018new_xinlang_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/46/2018new_xinlang_thumb.png","value": "[浪]","picid": ""}, {"phrase": "[羞嗒嗒]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/df/lxhxiudada_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/df/lxhxiudada_thumb.gif","value": "[羞嗒嗒]","picid": ""}, {"phrase": "[好爱哦]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/74/lxhainio_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/74/lxhainio_thumb.gif","value": "[好爱哦]","picid": ""}, {"phrase": "[偷乐]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fa/lxhtouxiao_thumb.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fa/lxhtouxiao_thumb.gif","value": "[偷乐]","picid": ""}, {"phrase": "[赞啊]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/00/lxhzan_thumb.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/00/lxhzan_thumb.gif","value": "[赞啊]","picid": ""}, {"phrase": "[笑哈哈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/32/lxhwahaha_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/32/lxhwahaha_thumb.gif","value": "[笑哈哈]","picid": ""}, {"phrase": "[好喜欢]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d6/lxhlike_thumb.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d6/lxhlike_thumb.gif","value": "[好喜欢]","picid": ""}, {"phrase": "[求关注]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ac/lxhqiuguanzhu_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ac/lxhqiuguanzhu_thumb.gif","value": "[求关注]","picid": ""}, {"phrase": "[胖丁微笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/68/film_pangdingsmile_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/68/film_pangdingsmile_thumb.png","value": "[胖丁微笑]","picid": ""}, {"phrase": "[弱]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3d/2018new_ruo_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3d/2018new_ruo_org.png","value": "[弱]","picid": ""}, {"phrase": "[NO]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_no_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_no_org.png","value": "[NO]","picid": ""}, {"phrase": "[haha]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1d/2018new_hahashoushi_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1d/2018new_hahashoushi_org.png","value": "[haha]","picid": ""}, {"phrase": "[加油]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9f/2018new_jiayou_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9f/2018new_jiayou_org.png","value": "[加油]","picid": ""}, {"phrase": "[佩奇]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/hot_pigpeiqi_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c6/hot_pigpeiqi_thumb.png","value": "[佩奇]","picid": ""}, {"phrase": "[大侦探皮卡丘微笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/pikaqiu_weixiao_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b3/pikaqiu_weixiao_thumb.png","value": "[大侦探皮卡丘微笑]","picid": ""}, {"phrase": "[圣诞老人]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/xmax_oldman01_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/xmax_oldman01_thumb.png","value": "[圣诞老人]","picid": ""}, {"phrase": "[紫金草]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e5/gongjiri_zijinhua_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e5/gongjiri_zijinhua_thumb.png","value": "[紫金草]","picid": ""}, {"phrase": "[文明遛狗]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/gongyi_wenminglgnew_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/gongyi_wenminglgnew_thumb.png","value": "[文明遛狗]","picid": ""}, {"phrase": "[神马]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/60/horse2_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/60/horse2_thumb.gif","value": "[神马]","picid": ""}, {"phrase": "[马到成功]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b0/mdcg_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b0/mdcg_thumb.gif","value": "[马到成功]","picid": ""}, {"phrase": "[炸鸡啤酒]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/zhajibeer_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/zhajibeer_thumb.gif","value": "[炸鸡啤酒]","picid": ""}, {"phrase": "[最右]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/be/remen_zuiyou180605_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/be/remen_zuiyou180605_thumb.png","value": "[最右]","picid": ""}, {"phrase": "[织]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/41/zz2_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/41/zz2_thumb.gif","value": "[织]","picid": ""}, {"phrase": "[五仁月饼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/10/2018zhongqiu_yuebing_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/10/2018zhongqiu_yuebing_thumb.png","value": "[五仁月饼]","picid": ""}, {"phrase": "[给你小心心]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ca/qixi2018_xiaoxinxin_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ca/qixi2018_xiaoxinxin_thumb.png","value": "[给你小心心]","picid": ""}, {"phrase": "[吃狗粮]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0b/qixi2018_chigouliang_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0b/qixi2018_chigouliang_thumb.png","value": "[吃狗粮]","picid": ""}, {"phrase": "[弗莱见钱眼开]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/83/2018newyear_richdog_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/83/2018newyear_richdog_thumb.gif","value": "[弗莱见钱眼开]","picid": ""}, {"phrase": "[超新星全运会]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/huodong_starsports_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/huodong_starsports_thumb.png","value": "[超新星全运会]","picid": ""}, {"phrase": "[看涨]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fe/kanzhangv2_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fe/kanzhangv2_thumb.gif","value": "[看涨]","picid": ""}, {"phrase": "[看跌]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c5/kandiev2_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c5/kandiev2_thumb.gif","value": "[看跌]","picid": ""}, {"phrase": "[带着微博去旅行]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ec/eventtravel_org.gif","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ec/eventtravel_thumb.gif","value": "[带着微博去旅行]","picid": ""}, {"phrase": "[星星]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/76/hot_star171109_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/76/hot_star171109_thumb.png","value": "[星星]","picid": ""}, {"phrase": "[半星]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/hot_halfstar_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f9/hot_halfstar_thumb.png","value": "[半星]","picid": ""}, {"phrase": "[空星]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ff/hot_blankstar_org.png","hot": false,"common": false,"category": "其他","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ff/hot_blankstar_thumb.png","value": "[空星]","picid": ""}, {"phrase": "[小黄人微笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f0/xhrnew_weixiao_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/f0/xhrnew_weixiao_org.png","value": "[小黄人微笑]","picid": ""}, {"phrase": "[小黄人剪刀手]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/63/xhrnew_jiandaoshou_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/63/xhrnew_jiandaoshou_org.png","value": "[小黄人剪刀手]","picid": ""}, {"phrase": "[小黄人不屑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b2/xhrnew_buxie_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/b2/xhrnew_buxie_org.png","value": "[小黄人不屑]","picid": ""}, {"phrase": "[小黄人高兴]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/41/xhrnew_gaoxing_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/41/xhrnew_gaoxing_org.png","value": "[小黄人高兴]","picid": ""}, {"phrase": "[小黄人惊讶]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fd/xhrnew_jingya_thumb.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/fd/xhrnew_jingya_thumb.png","value": "[小黄人惊讶]","picid": ""}, {"phrase": "[小黄人委屈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/79/xhrnew_weiqu_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/79/xhrnew_weiqu_org.png","value": "[小黄人委屈]","picid": ""}, {"phrase": "[小黄人坏笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/be/xhrnew_huaixiao_thumb.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/be/xhrnew_huaixiao_thumb.png","value": "[小黄人坏笑]","picid": ""}, {"phrase": "[小黄人白眼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/xhrnew_baiyan_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/xhrnew_baiyan_org.png","value": "[小黄人白眼]","picid": ""}, {"phrase": "[小黄人无奈]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/15/xhrnew_wunai_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/15/xhrnew_wunai_thumb.png","value": "[小黄人无奈]","picid": ""}, {"phrase": "[小黄人得意]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c8/xhrnew_deyi_org.png","hot": false,"common": false,"category": "小黄人","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/c8/xhrnew_deyi_thumb.png","value": "[小黄人得意]","picid": ""}, {"phrase": "[钢铁侠]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/27/avengers_ironman01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/27/avengers_ironman01_thumb.png","value": "[钢铁侠]","picid": ""}, {"phrase": "[美国队长]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d8/avengers_captain01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d8/avengers_captain01_thumb.png","value": "[美国队长]","picid": ""}, {"phrase": "[雷神]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3c/avengers_thor01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/3c/avengers_thor01_thumb.png","value": "[雷神]","picid": ""}, {"phrase": "[浩克]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/44/avengers_hulk01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/44/avengers_hulk01_thumb.png","value": "[浩克]","picid": ""}, {"phrase": "[黑寡妇]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0e/avengers_blackwidow01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/0e/avengers_blackwidow01_thumb.png","value": "[黑寡妇]","picid": ""}, {"phrase": "[鹰眼]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/avengers_clint01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/93/avengers_clint01_thumb.png","value": "[鹰眼]","picid": ""}, {"phrase": "[惊奇队长]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/44/avengers_captainmarvel01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/44/avengers_captainmarvel01_thumb.png","value": "[惊奇队长]","picid": ""}, {"phrase": "[奥克耶]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/avengers_aokeye01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/avengers_aokeye01_thumb.png","value": "[奥克耶]","picid": ""}, {"phrase": "[蚁人]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cc/avengers_antman01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/cc/avengers_antman01_thumb.png","value": "[蚁人]","picid": ""}, {"phrase": "[灭霸]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ce/avengers_thanos01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/ce/avengers_thanos01_thumb.png","value": "[灭霸]","picid": ""}, {"phrase": "[蜘蛛侠]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/avengers_spiderman01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/e2/avengers_spiderman01_thumb.png","value": "[蜘蛛侠]","picid": ""}, {"phrase": "[洛基]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1f/avengers_locki01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/1f/avengers_locki01_thumb.png","value": "[洛基]","picid": ""}, {"phrase": "[奇异博士]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9c/avengers_drstranger01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/9c/avengers_drstranger01_thumb.png","value": "[奇异博士]","picid": ""}, {"phrase": "[冬兵]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/91/avengers_wintersolider01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/91/avengers_wintersolider01_thumb.png","value": "[冬兵]","picid": ""}, {"phrase": "[黑豹]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/avengers_panther01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/86/avengers_panther01_thumb.png","value": "[黑豹]","picid": ""}, {"phrase": "[猩红女巫]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a9/avengers_witch01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/a9/avengers_witch01_thumb.png","value": "[猩红女巫]","picid": ""}, {"phrase": "[幻视]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/07/avengers_vision01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/07/avengers_vision01_thumb.png","value": "[幻视]","picid": ""}, {"phrase": "[星爵]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/avengers_starlord01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/avengers_starlord01_thumb.png","value": "[星爵]","picid": ""}, {"phrase": "[格鲁特]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7a/avengers_gelute01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7a/avengers_gelute01_thumb.png","value": "[格鲁特]","picid": ""}, {"phrase": "[螳螂妹]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7c/avengers_mantis01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/7c/avengers_mantis01_thumb.png","value": "[螳螂妹]","picid": ""}, {"phrase": "[无限手套]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/38/avengers_gauntlet01_org.png","hot": false,"common": false,"category": "复仇者联盟","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/38/avengers_gauntlet01_thumb.png","value": "[无限手套]","picid": ""}, {"phrase": "[大毛略略]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d0/yunying_damaoluelue_org.png","hot": false,"common": false,"category": "雪人奇缘","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/d0/yunying_damaoluelue_thumb.png","value": "[大毛略略]","picid": ""}, {"phrase": "[大毛惊讶]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4d/yunying_damaojingya_org.png","hot": false,"common": false,"category": "雪人奇缘","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/4d/yunying_damaojingya_thumb.png","value": "[大毛惊讶]","picid": ""}, {"phrase": "[大毛微笑]","type": "face","url": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/da/yunying_damaoweixiao_org.png","hot": false,"common": false,"category": "雪人奇缘","icon": "http://img.t.sinajs.cn/t4/appstyle/expression/ext/normal/da/yunying_damaoweixiao_thumb.png","value": "[大毛微笑]","picid": ""}];
					var groups = {},
						categories = [],
						map = {};
					emotions.forEach(emotion => {
						var cate = emotion.category.length > 0 ? emotion.category : '默认';
						if (!groups[cate]) {
							groups[cate] = [];
							categories.push(cate);
						}
						groups[cate].push(emotion);
						map[emotion.phrase] = emotion.icon;
					});
					return {
						groups,
						categories,
						map
					};
				}
			}
		});
	});
}
});