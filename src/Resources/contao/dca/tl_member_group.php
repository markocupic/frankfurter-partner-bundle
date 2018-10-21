<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

// Add selector
$GLOBALS['TL_DCA']['tl_member_group']['palettes']['__selector__'][] = 'hasPartnerAbo';

// Add subpalettes
$GLOBALS['TL_DCA']['tl_member_group']['subpalettes']['hasPartnerAbo'] = 'partnerAbo';


// Extend the default palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('partner_legend', 'title_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(array('hasPartnerAbo'), 'partner_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member_group')
;

/*
 * Add the fields
 */
$GLOBALS['TL_DCA']['tl_member_group']['fields']['hasPartnerAbo'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['hasPartnerAbo'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_member_group']['fields']['partnerAbo'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['partnerAbo'],
    'exclude' => true,
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'inputType' => 'select',
    'options' => Contao\Config::get('partnerAbos'),
    'eval' => ['tl_class' => 'clr'],
    'sql' => "varchar(128) NOT NULL default ''",
];

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