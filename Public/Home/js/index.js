var $alertModal = $('.alert-modal');

$('.loan-car-list>ul').slimScroll({
    height: '260px'
});

var $carList = $('.loan-car-list');
var $carInput = $('.loan-car-type').find('input[name="car_models_name"]');
$carInput
    .on('click',function(){
        var $cityBox = $('.cityBox');
        $cityBox.addClass('hide');
        $carList.show();
    })
;
$carList
    .on('click','ul li',function(e){
        e.stopPropagation();
        var _this = $(this);
        if(_this.find('li.sub-list-item').length>0){
            _this.find('.sub-list').toggle();
            _this.siblings().find('.sub-list').hide();
            return false;
        }

        var brandName = '';
        var $parentLi = _this.parents('li.list-item');
        if($parentLi.length>0){
            brandName = $parentLi.find('span.brand-name').text();
        }
        var id = $(this).attr('data-id');
        var text = $(this).text();
        $carInput.attr('data-id',id);
        $carInput.val(brandName?brandName+'-'+text:text);
        _this.addClass('active').siblings().removeClass('active');
        $parentLi.addClass('active').siblings().removeClass('active').find('.sub-list').removeClass('active');

        $carList.hide();
    })
;
$(document).on('click',function(){
    $carList.hide();
});


$('.loan-city ,.loan-car-type')
    .on('click','input',function(e){
        e.stopPropagation();
        $('.loan-result').hide();
        $(this).parents('.form-group ').find('.loan-result').show();
    })
;

var $ul = $('.new-orders ul');
var $li = $ul.find('li');
$ul.append($li.eq(0).clone());
var num = 0;
setInterval(function(){
    num++;
    if(num == $ul.find('li').length){
        num = 1;
        $ul.css({
            top:0
        });
    }
    $ul.animate({
        top: -num*30
    }, 300 );
},3000);


function check(obj){
    //获取数据
    var car_models_name = $("input[name=car_models_name]").val();     //获取车型id
    var areaid = $("input[name=areaid]").val();     //获取城市id
    var username = $("input[name=username]").val().trim();     //获取用户姓名
    var phone = $("input[name=phone]").val();     //获取手机号
    var pregname = /^[\u4e00-\u9fa5a-zA-Z]{1,32}$/;     //姓名正则
    var pregphone = /^1(30|31|32|33|34|35|36|37|38|39|45|47|50|51|52|53|55|56|57|58|59|70|71|73|76|77|78|80|81|82|83|84|85|86|87|88|89)\d{8}$/;      //手机验证
    if(car_models_name == ''){
        showAlert('error','车型不能为空','');
        return false;
    }else{
        $("input[name=series_id]").val($(".sub-list-item.active").attr('data-id'));     //获取车型id
    }
    if(areaid == ''){
        showAlert('error','城市不能为空','');
        return false;
    }
    if(!pregname.test(username) || username=='' || username==null){
        showAlert('error','名称不能为空','请输入1~32中英文且');
        return false;
    }
    if(!pregphone.test(phone) || phone=='' || phone==null){
        showAlert('error','请输入正确的手机号','');
        return false;
    }

    $.post("/Home/Index/apply", $(obj).serialize(), function(msg){
        var $loanForm = $('.loan-form');

        if( msg.code == 1 ){
            showAlert('','提交成功','您的金融顾问会马上联系您');

            setTimeout(function(){
                var $loanForm = $('.loan-form');
                $loanForm.get(0).reset();
                $loanForm.find('li.active').removeClass('active');
                hideAlert();
            },1500)

        }else{
            showAlert('error','申请提交失败','请重新尝试');
        }
    });
}

$(function(){
    $('.loan-form').on('submit',function(){
        check(this);
        return false;
    });
});


$('.loan-application').on('click','.close',function(){
    var $loanFormWrap = $('.loan-form-wrap');
    var $loanForm = $loanFormWrap.find('.loan-form');
    $loanForm.get(0).reset();
    $loanForm.find('li.active').removeClass('active');
    $loanFormWrap.fadeOut();
});


$(document).on('click','.alert-modal .close',function(){
    console.log('close alert');
    var $loanForm = $('.loan-form');
    // $loanForm.get(0).reset();
    $loanForm.find('li.active').removeClass('active');
    hideAlert();
});

function showAlert(type,title,subtitle){
    var $alertModal = $alertModal || $('.alert-modal');
    $alertModal.removeClass('error').addClass(type);
    $alertModal.find('.alert-text').text(title||'贷款申请提交成功');
    $alertModal.find('.alert-sub-text').text(subtitle||'');
    $alertModal.fadeIn();
}
function hideAlert(){
    var $alertModal = $alertModal || $('.alert-modal');
    $alertModal.fadeOut();
    setTimeout(function(){
        $alertModal.removeClass('error');
        $alertModal.find('.alert-text').text('贷款申请提交成功');
        $alertModal.find('.alert-sub-text').text('服务人员稍后会与您联系');
    },500)
}

// 搜索车型
$(document).on('click', function(event){
    var className = event.target.className;
    switch (className) {
        case 'search-input-click':
            $('.search-input-list').css('display', 'flex')
            $('.search-list-a-z .active').click();
            break
        case 'search-item active':
            $('.search-input-list').css('display', 'flex')
            break
        default:
            $('.search-input-list').css('display', 'none')
    }
});

$(document).on('click', '.search-list-a-z > .search-item', function(){
    $(this).siblings('dt').removeClass('active');
    $(this).addClass('active');
    var initials = $(this).text();
    $.post("/home/index/getBrand", {"initials":initials}, function (msg) {
        if(msg.code != 200){
            return false;
        }

        $('.search-list-item1').empty();
        for(var i in msg.data){
            var data = msg.data[i];
            var html = "<dt class='search-item' data_id='"+ data.id+"'>"+ data.brand_name  +"</dt>";
            $('.search-list-item1').append(html);
        }
    }, "json");
});


$(document).on('click', '.search-list-item1 > .search-item', function(){
    $(this).siblings('dt').removeClass('active');
    $(this).addClass('active');
    $.post("/home/index/getSeries", {"brand_id": $(this).attr("data_id")}, function (msg) {
        if(msg.code != 200){
            return false;
        }

        $('.search-list-item2').empty();
        var html = "<dt class='search-item a' data_id='0'>全部</dt>";
        $('.search-list-item2').append(html);
        for(var i in msg.data){
            var data = msg.data[i];
            var html = "<dt class='search-item a' data_id='"+data.id+"'>"+data.series+"</dt>";
            $('.search-list-item2').append(html);
        }
    }, "json");
});

$(document).on('click', '.search-list-item2 > .search-item', function(){
    $(this).siblings('dt').removeClass('active');
    $(this).addClass('active');

    var brandObj = $('.search-list-item1 > .active');
    var seriesObj = $('.search-list-item2 > .active');
    $('.search-input').val( brandObj.text() + "，" + seriesObj.text());
    $('input[name="brand_id"]').val( brandObj.attr("data_id"));
    $('input[name="series_id"]').val(seriesObj.attr("data_id"));
});


/**
 * 新车二手车
 */
$(function(){
    $('.search-form input[name="is_used_car"]').on('change', function () {
        if( $(this).attr('id') == "c1"){
            $("#c2").attr("checked", false);
        }else{
            $("#c1").attr("checked", false);
        }
    });
});

// 二维码
$(document).on('mouseover', '.dow-app', function (){
    $('.dow-app-img').fadeIn()
})
$(document).on('click', function(event){
    var className = event.target.className;
    if (className != 'dow-app') {
        $('.dow-app-img').fadeOut()
    }
});


// 加盟
$(document).on('click', '.jiameng', function(){
    $('.pop-div,.jiameng-form').fadeIn();
    //$('.pop-div,.jiameng-form,.info-text').fadeIn()
    //setTimeout(() => {
    //    $('.info-text').fadeOut()
    //}, 3000);
});

$(document).on('click', '.pop-div', function(){
    $('.pop-div,.pop-cont').fadeOut()
});

$(function () {
    $(function(){
        $('.add_business').on('submit',function(){
            //获取数据
            var store_name = $(this).find("input[name=store_name]").val();
            var store_addr =  $(this).find("input[name=store_addr]").val();
            var phone =  $(this).find("input[name=phone]").val();     //获取手机号

            var pregname = /^[\u4e00-\u9fa5a-zA-Z]{1,32}$/;     //姓名正则
            var pregphone = /^1(30|31|32|33|34|35|36|37|38|39|45|47|50|51|52|53|55|56|57|58|59|70|71|73|76|77|78|80|81|82|83|84|85|86|87|88|89)\d{8}$/;      //手机验证
            if(store_name == ''){
                showAlert('error','店名不能空','');
                return false;
            }

            if(store_addr == ''){
                showAlert('error','地址不能为空','');
                return false;
            }

            if(!pregphone.test(phone) || phone=='' || phone==null){
                showAlert('error','请输入正确的手机号','');
                return false;
            }

            $.post("/Home/Business/add", $(this).serialize(), function(msg){
                var $loanForm = $(this);
                setTimeout(function(){
                    var $loanForm = $('.add_business');
                    $loanForm.get(0).reset();
                    hideAlert();
                },1500);

                if( msg.code == 1 ){
                    showAlert('','提交成功','管理员马上会联系您');
                    $('.pop-div,.pop-cont').fadeOut();
                }else{
                    showAlert('error','申请提交失败','请重新尝试');
                }
            });
        });
    });
});

