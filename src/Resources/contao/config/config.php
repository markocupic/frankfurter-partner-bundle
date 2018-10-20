<?php

// Frontend Modules
$GLOBALS['FE_MOD']['partner_catalog'] = array(
    'partnerFrontendForm' => 'Markocupic\FrankfurterPartnerBundle\Contao\Modules\PartnerFrontendForm',
);

/**
 * Hooks
 */
if (TL_MODE == 'BE')
{


}

if (TL_MODE == 'FE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/markocupicfrankfurterpartner/css/stylesheet.css';
    $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/markocupicfrankfurterpartner/js/sessiontabs.js';
}


// Front end form fields
$GLOBALS['TL_FFL']['formMultiText'] = 'FormMultiText';


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






