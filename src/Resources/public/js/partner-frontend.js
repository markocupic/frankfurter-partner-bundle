/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */


/** Scroll to form fields if there are errors **/
(function ($) {
    $(document).ready(function () {
        if ($('.message-box, .widget.error').length) {
            var interval = window.setInterval(function () {
                // Wait until the onload overlay has disapeared
                clearInterval(interval);
                window.setTimeout(function () {
                    window.scrollTo(0, $('.message-box, .widget.error').first().offset().top - 100);
                }, 100);
            }, 100);
        }
    });
})(jQuery);


// Ajax actions
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
                var fieldname = $(elImage).data('fieldname');
                var requesttoken = $(elImage).data('requesttoken');
                if (fileId !== undefined) {
                    var jqxhr = $.post(window.location.href, {
                        'REQUEST_TOKEN': requesttoken,
                        'xhr': 'true',
                        'action': 'removeImage',
                        'fileId': fileId,
                        'fieldname': fieldname
                    }).done(function (json) {
                        $(elImage).find('.image_container').fadeOut();
                        window.setTimeout(function () {
                            $(elImage).find('.image_container').remove();
                        }, 1000);
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
                        } else {
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
