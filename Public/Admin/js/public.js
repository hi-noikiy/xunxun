
    // 显示弹出
    function prompt_confirm($tipContent, url){
        var model = $('#confirm_content');
        model.find(".confirm_content").text($tipContent);
        model.find(".confirm_url").val(url);
        model.modal("show");
    }

    // 确认，跳转
    function urlSubmit(){
        var model = $('#confirm_content');
        var url=$.trim( model.find(".confirm_url").val());//获取会话中的隐藏属性URL
        window.location.href=url;
    }

