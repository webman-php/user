const webman = {
    success(msg, options) {
        webman.toast(msg, 'success', options);
    },
    error(msg, options) {
        webman.toast(msg, 'danger', options);
    },
    warning(msg, options) {
        webman.toast(msg, 'warning', options);
    },
    info(msg, options) {
        webman.toast(msg, 'dark', options);
    },
    toast(msg, type = 'primary', options = {}) {
        let delay = options.delay || (type === 'success' ? 700 : 2000);
        if (!$("#toast-box").length) {
            $('body').append('<div id="toast-box" style="width:260px;z-index:10001;position:fixed;right:20px;top:20px;"></div>');
        }
        $("#toast-box").append('<div class="toast" role="alert" data-delay="'+delay+'">\n' +
            '                <div class="toast-header">\n' +
            '                    <div class="rounded mr-2 bg-'+type+'" style="height:20px;width:20px;"></div>\n' +
            '                    <strong class="mr-auto">提示</strong>\n' +
            '                    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">\n' +
            '                        <span aria-hidden="true">&times;</span>\n' +
            '                    </button>\n' +
            '                </div>\n' +
            '                <div class="toast-body text-'+type+'">\n' +
            '                    '+msg+'\n' +
            '                </div>\n' +
            '            </div>');
        $('.toast').toast('show').on('hide.bs.toast', function () {
            $(this).remove();
            let toastBox = $("#toast-box");
            toastBox.html() === '' && toastBox.remove();
            let func = typeof options === 'function' ? options : options.end;
            func && func();
        });
    }
}


