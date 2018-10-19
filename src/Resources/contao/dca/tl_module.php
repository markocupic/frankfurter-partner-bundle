<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 16.10.2018
 * Time: 08:44
 */

// Palette
$GLOBALS['TL_DCA']['tl_module']['palettes']['partnerFrontendForm'] = '{title_legend},name,headline,type;{partner_frontend_form_settings};{template_legend:hide},customTpl,partnerFrontendFormFineuploaderTemplate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';


$GLOBALS['TL_DCA']['tl_module']['fields']['partnerFrontendFormFineuploaderTemplate'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['partnerFrontendFormFineuploaderTemplate'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_frankurter', 'getUploaderTemplates'),
    'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
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