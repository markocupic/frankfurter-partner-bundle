<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */


// notification_center_config.php
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['frankfurter_partner_bundle'] = array
(
    // Type
    'advice_admin_on_new_entries' => array
    (
        // Field in tl_nc_language
        //'email_sender_name'    => array(),
        //'email_sender_address' => array(),
        //'recipients'           => array(),
        //'email_replyTo'        => array(),
        'email_subject' => array('partner_name'),
        'email_text'    => array('partner_name', 'hostname', 'partner_id', 'partner_alias', 'publish_state', 'preview_token'),
        'email_html'    => array('partner_name', 'hostname', 'partner_id', 'partner_alias', 'publish_state', 'preview_token'),
    )
);