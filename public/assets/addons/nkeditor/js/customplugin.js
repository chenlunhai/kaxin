define(['nkeditor-core'], function (Nkeditor) {
    Nkeditor.plugin('multiimage', function (K) {
        var self = this, name = 'multiimage', lang = self.lang(name + '.'),
            allowImages = K.undef(self.allowImages, false);

        var click = function () {

            var html = [
                '<div class="ke-dialog-content-inner">',
                '<div class="ke-dialog-row ke-clearfix">',
                '<div class=""><div class="ke-inline-block ke-upload-button">' +
                '<form class="ke-upload-area ke-form nice-validator n-default" method="post" enctype="multipart/form-data" style="width: 266px;margin:50px auto;">' +
                '<span class="ke-button-common"><input type="button" class="ke-button-common ke-button" value="批量上传图片" style="width:128px;"></span><input type="file" class="ke-upload-file" name="imgFiles" multiple style="width:128px;left:0;right:inherit" tabindex="-1">' +
                '<span class="ke-button-common" style="margin-left:10px;"><input type="button" class="ke-button-common ke-button ke-select-image" style="width:128px;" value="从图片空间选择"></span>' +
                '</form>' +
                '</div></span></div>',
                '</div>',
                '</div>'
            ].join('');
            var dialog = self.createDialog({
                    name: name,
                    width: 450,
                    height: 260,
                    title: self.lang(name),
                    body: html,
                    noBtn: {
                        name: self.lang('no'),
                        click: function (e) {
                            self.hideDialog().focus();
                        }
                    }
                }),
                div = dialog.div;
            $("input[name=imgFiles]", div).change(function () {
                dialog.showLoading();
                var files = $(this).prop('files');
                $.each(files, function (i, file) {
                    self.beforeUpload.call(self, function (data) {
                        self.exec('insertimage', Fast.api.cdnurl(data.data.url));
                    }, file);
                });
                setTimeout(function () {
                    self.hideDialog().focus();
                }, 0);
            });
            $(".ke-select-image", div).click(function () {
                self.loadPlugin('filemanager', function () {
                    self.plugin.filemanagerDialog({
                        dirName: 'image',
                        multiple: true,
                        clickFn: function (urls) {
                            $.each(urls, function(i, url){
                                self.exec('insertimage', Fast.api.cdnurl(url));
                            });
                        }
                    });
                });
                self.hideDialog().focus();
                // parent.Fast.api.open("general/attachment/select?element_id=&multiple=true&mimetype=*", __('Choose'), {
                //     callback: function (data) {
                //         var urlArr = data.url.split(/\,/);
                //         $.each(urlArr, function () {
                //             var url = Fast.api.cdnurl(this);
                //             self.exec('insertimage', url);
                //         });
                //     }
                // });
            });
        };
        self.clickToolbar(name, click);
    });

    return Nkeditor;
});
