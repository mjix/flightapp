(function ($, window, document, undefined){
    if(!window.JSON) {
        window.JSON = {
            parse: function(sJSON) {
                return eval('(' + sJSON + ')'); },
            stringify: (function() {
                var toString = Object.prototype.toString;
                var isArray = Array.isArray || function(a) {
                    return toString.call(a) === '[object Array]'; };
                var escMap = { '"': '\\"', '\\': '\\\\', '\b': '\\b', '\f': '\\f', '\n': '\\n', '\r': '\\r', '\t': '\\t' };
                var escFunc = function(m) {
                    return escMap[m] || '\\u' + (m.charCodeAt(0) + 0x10000).toString(16).substr(1); };
                var escRE = /[\\"\u0000-\u001F\u2028\u2029]/g;
                return function stringify(value) {
                    if (value == null) {
                        return 'null';
                    } else if (typeof value === 'number') {
                        return isFinite(value) ? value.toString() : 'null';
                    } else if (typeof value === 'boolean') {
                        return value.toString();
                    } else if (typeof value === 'object') {
                        if (typeof value.toJSON === 'function') {
                            return stringify(value.toJSON());
                        } else if (isArray(value)) {
                            var res = '[';
                            for (var i = 0; i < value.length; i++)
                                res += (i ? ', ' : '') + stringify(value[i]);
                            return res + ']';
                        } else if (toString.call(value) === '[object Object]') {
                            var tmp = [];
                            for (var k in value) {
                                if (value.hasOwnProperty(k))
                                    tmp.push(stringify(k) + ': ' + stringify(value[k]));
                            }
                            return '{' + tmp.join(', ') + '}';
                        }
                    }
                    return '"' + value.toString().replace(escRE, escFunc) + '"';
                };
            })()
        };
    }
})(window.jQuery, window, document);

(function ($, window, document, undefined){
    var html = [
        '<div class="box box-fullwin box-alert" id="J_alertbox">',
        '    <div class="bodymask"></div>',
        '    <div class="box-solid box-inner">',
        '        <div class="box-header with-border">',
        '            <h3 class="box-title title">提示</h3>',
        '            <div class="box-tools pull-right">',
        '                <a class="btn btn-box-tool btn-close">&times;</a>',
        '            </div>',
        '        </div>',
        '        <div class="box-body"> </div>',
        '        <div class="box-footer">',
        '            <a href="javascript:void(0);" class="btn btn-default btn-cancel">取消</a>',
        '            <a href="javascript:void(0);" class="btn btn-default btn-info btn-ok">确定</a>',
        '        </div>',
        '    </div>',
        '</div>',
    ].join('');

    var _initAlert = function(){
        var $alertBox = $('#J_alertbox'),
            config = _initAlert.setting;

        if($alertBox.length<1){
            $(document.body).append(html);
            $alertBox = $('#J_alertbox');
        }

        if(!_initAlert.isInit){
            _initAlert.isInit = true;

            var context = {
                close : function(){
                    $('#J_alertbox').hide();
                }
            };

            $alertBox.find('.btn-cancel').on('click', function(e){
                config = _initAlert.setting;
                if(config.cancel){
                    config.cancel.call(context, e);
                }else{
                    context.close();
                }
            });
            $alertBox.find('.btn-ok').on('click', function(e){
                config = _initAlert.setting;
                if(config.ok){
                    config.ok.call(context, e);
                    context.close();
                }else{
                    context.close();
                }
            });
        }
        if(config.hideCancel){
            $alertBox.addClass('no-cancel');
        }else{
            $alertBox.removeClass('no-cancel');
        }

        $alertBox.find('.title').html(config.title);
        $alertBox.find('.box-body').html(config.content);
        $alertBox.find('.btn-ok').html(config.okWording);
        $alertBox.find('.btn-cancel').html(config.cancelWording);
        $('#J_alertbox').show();
    };

    $.extend({
        showAlert : function(setting, okFn){
            var _defaut = {
                title : '提示',
                content : '描述',
                hideCancel : false,
                ok : okFn,
                cancel : false,
                okWording : '确定',
                cancelWording : '取消'
            };
            if(typeof setting==='string'){
                setting = {
                    hideCancel : true,
                    content : setting,
                    okWording : '确定',
                    ok : okFn
                };
            }
            _initAlert.setting = $.extend(_defaut, setting);
            _initAlert();
        }
    });

})(window.jQuery, window, document);

//some functions
(function ($, window, document, undefined){
    $.fn.serializeObject = function(){
        var formObj = {};

        $.each(this, function(i, obj){
            var inputs = $(obj).serializeArray();
            $.each(inputs, function (i, input) {
                formObj[input.name] = input.value;
            });
        });
        return formObj;
    };

    $.fn.getFormValues = function(){
        var $sele = this,
            form = {};
        $sele.find('[index]').each(function(i, obj){
            var $this = $(obj);
            form[$this.attr('index')] = $this.val();
        });
        return form;
    };

    $.fn.setFormValues = function(data){
        var $sele = this;
        $.each(data, function(k, v){
            $sele.find('[name="'+k+'"]').val(v);
        });
        return $sele;
    };

    $.fn.createLoadMask = function(options){
        var $sele = this,
            temp = [
                '<div class="ym-mask-box" style="position:fixed; left:0; right:0; top:0; bottom:0; background:rgba(255,255,255,.9); z-index:2000; display:none;">',
                '    <div class="mask-inner" style="width:600px; min-height:200px; position:absolute; top:50%; left:50%; margin-left:-300px; margin-top:-100px; background:url(http://pc1.gtimg.com/softmgr/images/webclinic/loading.png) no-repeat center 10px;">',
                '        <div class="mask-txt" style="margin-top:80px; text-align:center; color:#10ccfb; font-size:13px; line-height:26px;"></div>',
                '    </div>',
                '</div>'
            ].join('');
        if($sele.find('.ym-mask-box').length<1){
            $sele.append(temp);
        }

        options = options || {};
        if($.type(options)=='string'){
            options = {msg:options};
        }
        if(!options.msg){
            options.msg = '正在请求中，请稍等...&nbsp;<br><div style="color:#bbb;">来，跟着开发一起数，<span class="mask-time">1</span>只羊</div>';
        }
        $sele.find('.mask-txt').html(options.msg);

        var sheepTimer = 0,
            delayTimer = 0,
            $maskbox = $sele.find('.ym-mask-box');
        return {
            show : function(delay){
                clearInterval(sheepTimer);
                delay = delay || 200;
                delayTimer = setTimeout(function(){
                    var time = 1;
                    $maskbox.show();
                    sheepTimer = setInterval(function(){
                        $maskbox.find('.mask-time').text(++time);
                    }, 1000);
                }, delay);
                return this;
            },

            hide : function(){
                $maskbox.hide();
                clearInterval(sheepTimer);
                clearTimeout(delayTimer);
                return this;
            }
        };
    };
})(window.jQuery, window, document);

