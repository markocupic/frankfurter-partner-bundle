<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 16.10.2018
 * Time: 08:44
 */

// Palette
$GLOBALS['TL_DCA']['tl_module']['palettes']['partnerFrontendForm'] = '{title_legend},name,headline,type;{member_picture_feed_settings},memberPictureFeedUploadFolder,imgSize,memberPictureFeedUploadPictureLimit;{template_legend:hide},customTpl,memberPictureFeedUploadCustomUploaderTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';


// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['memberPictureFeedUploadFolder'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['memberPictureFeedUploadFolder'],
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => array('fieldType' => 'radio', 'filesOnly' => false, 'mandatory' => true, 'tl_class' => 'clr'),
    'sql'       => "binary(16) NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['memberPictureFeedUploadPictureLimit'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['memberPictureFeedUploadPictureLimit'],
    'exclude'   => true,
    'default'   => 3,
    'inputType' => 'text',
    'eval'      => array('mandatory' => false, 'rgxp' => 'natural', 'tl_class' => 'w50'),
    'sql'       => "smallint(5) unsigned NOT NULL default '0'"
);


$GLOBALS['TL_DCA']['tl_module']['fields']['memberPictureFeedUploadCustomUploaderTpl'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['memberPictureFeedUploadCustomUploaderTpl'],
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