<?php

/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

$rootDir = Contao\System::getContainer()->getParameter('kernel.project_dir');


// Add notification center configs
require_once($rootDir . '/src/markocupic/frankfurter-partner-bundle/src/Resources/contao/config/notification_center_config.php');


// Frontend Modules
$GLOBALS['FE_MOD']['partner_catalog'] = array(
    'partnerFrontendForm'               => 'Markocupic\FrankfurterPartnerBundle\Contao\Modules\PartnerFrontendForm',
    'customcatalogreaderpartnerpreview' => 'PCT\CustomElements\Plugins\CustomCatalog\Frontend\ModuleReaderPartnerPreview'
);

// Cron
$GLOBALS['TL_CRON']['daily']['adminAdvice'] = array('Markocupic\FrankfurterPartnerBundle\Contao\Notifications\PartnerNotification', 'sendNotification');

// Notification id
$GLOBALS['TL_CONFIG']['notification_advice_admin_on_new_entries'] = 1;

if (TL_MODE == 'FE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/markocupicfrankfurterpartner/css/stylesheet.css';
    $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/markocupicfrankfurterpartner/js/partner-frontend.js';
}


// Front end form fields
$GLOBALS['TL_FFL']['formMultiText'] = 'FormMultiText';

// Add one or more cat pid from tl_pct_customelement_tags
// This is used to generate the options in the PartnerFrontendForm for generating ffm_partner_cat
$GLOBALS['TL_CONFIG']['partnerCatPid'] = array(31);

// Partner Abos
$GLOBALS['TL_CONFIG']['partnerAbos'] = array('business', 'business-pro', 'business-premium');

// Partner Abo settings
$GLOBALS['TL_CONFIG']['partnerAboAllowedGalleryImages'] = array(
    'business'         => 4,
    'business-pro'     => 8,
    'business-premium' => 16
);

$GLOBALS['TL_CONFIG']['partnerAboAllowedCategories'] = array(
    'business'         => 1,
    'business-pro'     => 2,
    'business-premium' => 4
);

$GLOBALS['TL_CONFIG']['partnerAboAllowedImagesOurBrands'] = array(
    'business'         => 3,
    'business-pro'     => 6,
    'business-premium' => 9
);

$GLOBALS['TL_CONFIG']['partnerAboAllowedProducts'] = array(
    'business'         => 0,
    'business-pro'     => 3,
    'business-premium' => 9
);

$GLOBALS['TL_CONFIG']['partnerAboAllowYoutubeEmbed'] = array(
    'business'         => false,
    'business-pro'     => false,
    'business-premium' => true
);






