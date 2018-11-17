<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'addPreviewPage';
$GLOBALS['TL_DCA']['tl_module']['palettes']['partnerFrontendForm'] = '{title_legend},name,headline,type;{partner_frontend_form_settings},addPreviewPage;{template_legend:hide},customTpl,partnerFrontendFormFineuploaderTemplate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['partnerBenefitForm'] = '{title_legend},name,headline,type;{partner_benefit_form_settings};{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['customcatalogreaderpartnerpreview'] = '{title_legend},name,headline,type;{config_legend},customcatalog;{list_legend},customcatalog_setVisibles;{filter_legend},customcatalog_filter_actLang;{template_legend:hide},customcatalog_template,customcatalog_mod_template;{comment_legend:hide},com_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

// Subpalettes
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['addPreviewPage'] = 'previewPage';


// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['partnerFrontendFormFineuploaderTemplate'] = array(
    'label'            => &$GLOBALS['TL_LANG']['tl_module']['partnerFrontendFormFineuploaderTemplate'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => array('tl_module_frankurter', 'getUploaderTemplates'),
    'eval'             => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'),
    'sql'              => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['addPreviewPage'] = array(

    'label'     => &$GLOBALS['TL_LANG']['tl_module']['addPreviewPage'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true),
    'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['previewPage'] = array(
    'label'      => &$GLOBALS['TL_LANG']['tl_module']['previewPage'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => array('fieldType' => 'radio', 'tl_class' => 'clr'),
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy')
);


/**
 * Class tl_module_frankurter
 */
class tl_module_frankurter extends Backend
{

    /**
     * Return all event templates as array
     *
     * @return array
     */
    public function getUploaderTemplates()
    {
        return $this->getTemplateGroup('fineuploader_frontend_');
    }

}