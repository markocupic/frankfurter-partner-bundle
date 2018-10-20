// Always open last used bootstrap tab, when reloading the page
// Add session-tabs class to the nav tag
// Add data-sessiontabs="someId" to the nav tag

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