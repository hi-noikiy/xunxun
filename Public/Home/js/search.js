$(function(){
    $(document).on('click', '.hot-city span', function(){
        $('.hot-city span').removeClass('active');
        $(this).addClass('active')
    })
    $(document).on('click', '.city-list span', function(){
        $('.city-list span').removeClass('active');
        $(this).addClass('active')
    })
    $(document).on('click', '.sorting-operation li', function(){
        $('.sorting-operation li').removeClass('active');
        $(this).addClass('active')
    })
})