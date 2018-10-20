
(function ($) {
    $(document).ready(function () {
        "use strict";
        $('.partner-main-image, .partner-logo-image, .partner-product-image, .partner-brand-image').find('.remove-image').on('click', function (e) {

            e.preventDefault();
            e.stopPropagation();
            $(this).off();
            var elImage = $(this).closest('[data-file-id]');
            if (elImage.length) {
                var fileId = $(elImage).data('file-id');
                var fieldname =  $(elImage).data('fieldname');
                var requesttoken = $(elImage).data('requesttoken');
                if (fileId !== undefined) {
                    var jqxhr = $.post(window.location.href, {
                        'REQUEST_TOKEN': requesttoken,
                        'xhr': 'true',
                        'action': 'removeImage',
                        'fileId': fileId,
                        'fieldname': fieldname
                    }).done(function (json) {
                        $(elImage).fadeOut();
                        window.setTimeout(function(){
                            $(elImage).remove();
                        },1000);
                    });
                }
            }
        });

        $('.partner-main-image, .partner-logo-image, .partner-product-image, .partner-brand-image').on('click touchmove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).off();
            var elImage = $(this).closest('[data-file-id]');
            if (elImage.length) {
                var fileId = $(elImage).data('file-id');
                var requesttoken = $(elImage).data('requesttoken');
                if (fileId !== undefined) {
                    var jqxhr = $.post(window.location.href, {
                        'REQUEST_TOKEN': requesttoken,
                        'xhr': 'true',
                        'action': 'rotateImage',
                        'fileId': fileId
                    }).done(function (json) {
                        json = $.parseJSON(json);
                        if (json.status === 'success') {
                            window.location.reload();
                        }else{
                            alert('Es ist ein Fehler aufgetreten. Bitte kontrollieren Sie die Verbidnung.')
                        }
                    });
                }
            }
        });

    });

})(jQuery);

// Sessiontabs
jQuery(document).ready(function () {
    jQuery('.form-parts-selector-area .form-parts-selector-item').click(function (e) {
        e.preventDefault();
        hideAll();
        var dataSelector = jQuery(this).data('filter');
        jQuery(this).addClass('active');
        jQuery('.form-part[data-formpartname="' + dataSelector + '"]').show();
        storeInSessionStorrage('activeformtab', jQuery(this).prop('id'));
    });
    reset();


    function storeInSessionStorrage($strKey, strId) {
        sessionStorage.setItem($strKey, strId);
    }

    function getFromSessionStorrage(strKey) {
        if (sessionStorage.getItem(strKey) !== '') {
            return sessionStorage.getItem(strKey);
        }
        return null;
    }

    function hideAll() {
        jQuery('.form-parts .form-part').hide();
        jQuery('.form-parts-selector-area .form-parts-selector-item').removeClass('active');
    }

    function reset() {
        hideAll();
        if (getFromSessionStorrage('activeformtab') !== null) {
            var id = getFromSessionStorrage('activeformtab');

            if (jQuery('#' + id).length) {
                jQuery('#' + id).addClass('active').trigger('click');
            }
        } else {
            jQuery('.form-parts .form-part').first().show();
            jQuery('.form-parts-selector-area .form-parts-selector-item').first().addClass('active');
        }

    }

});

/* ]]> */