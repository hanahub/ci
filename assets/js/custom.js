function fb_block(selector) {
    jQuery(selector).block({ message: '<div class="fb_block"><img src="' + base_url + 'assets/images/ajax-loader.gif" /></div>',
                            overlayCSS: { backgroundColor: '#fff', opacity: '0.6' },
                            css: {border: 'none', background: "transparent", width: "188px", top: "30%" } });
}

function fb_unblock(selector) {
    jQuery(selector).unblock({fadeOut:  0});
}


var spanSorting = '<span class="arrow-hack sort">&nbsp;&nbsp;&nbsp;</span>';
var spanAsc = '<span class="arrow-hack asc">&nbsp;&nbsp;&nbsp;</span>';
var spanDesc = '<span class="arrow-hack desc">&nbsp;&nbsp;&nbsp;</span>';

jQuery(document).ready(function($) {
    $(document).on("change", "#check_all_products", function() {
        if ($(this).is(":checked")) 
            $(".product_checkbox").prop("checked", true);
        else 
            $(".product_checkbox").prop("checked", false);
    });
    
    table = $(".dataTable").DataTable({                        
                "bLengthChange": true,
                "searching": true,
                "lengthMenu": [10, 15, 25, 50, 75, 100],
                "pageLength": 15,
                "aaSorting": [0],
                "aoColumnDefs": [
                    { 'bSortable': false, 'aTargets': [] }
                ]
    });
    
    function init_dataTable() {
        $(".dataTable thead th").each(function(i, th) {
            $(th).find('.arrow-hack').remove();
            var html = $(th).html(),
                cls = $(th).attr('class');
            if (cls.indexOf("sorting_asc") > -1) {
                $(th).html(html+spanAsc);   
            } else if (cls.indexOf("sorting_desc") > -1) {
                $(th).html(html+spanDesc);
            } else {
                $(th).html(html+spanSorting);
            }
        });     
    }
    init_dataTable();
    $(".dataTable").on('click', 'th', function() {
        init_dataTable();
    });
});
        