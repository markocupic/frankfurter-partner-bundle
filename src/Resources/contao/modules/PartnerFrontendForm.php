<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 16.10.2018
 * Time: 08:34
 */

namespace Markocupic\FrankfurterPartnerBundle\Contao\Modules;

use Contao\CcCardealerModel;
use Contao\Controller;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Dbafs;
use Contao\Files;
use Contao\FilesModel;
use Contao\Folder;
use Contao\File;
use Contao\Frontend;
use Contao\Module;
use Contao\BackendTemplate;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\Validator;
use Haste\Util\FileUpload;
use Patchwork\Utf8;
use Haste\Form\Form;
use Contao\Input;
use Contao\Environment;
use Contao\System;
use Contao\Config;
use Psr\Log\LogLevel;


/**
 * Class PartnerFrontendForm
 * @package Markocupic\FrankfurterPartnerBundle\Contao\Modules
 */
class PartnerFrontendForm extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_partnerFrontendForm';

    /**
     * @var
     */
    protected $objUser;

    /**
     * @var
     */
    protected $objForm;

    /**
     * @var
     */
    protected $hasResized;

    /**
     * @var array
     */
    protected $arrMessages = array();

    /**
     * @var string
     */
    protected $flashMessageKey = 'mod_partnerFrontendForm';

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {


        if (TL_MODE == 'BE')
        {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['partnerFrontendForm'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }


        if (FE_USER_LOGGED_IN)
        {
            $this->objUser = FrontendUser::getInstance();
        }
        else
        {
            return '';
        }

        // Handle ajax requests
        if ((!is_array($_FILES) || empty($_FILES)) && Environment::get('isAjaxRequest'))
        {
            $this->handleAjaxRequest();
            exit();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $this->generateTestForm();
        $this->Template->form = $this->objForm;
    }

    protected function generateTestForm()
    {
        $objForm = new Form('form-member-picture-feed-upload', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });


        $url = Environment::get('uri');
        $objForm->setFormActionFromUri($url);

        // Add hidden fields REQUEST_TOKEN & FORM_SUBMIT
        $objForm->addContaoHiddenFields();

        $objDb = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE memberid=?')->limit(1)->execute($this->objUser->id);
        if (!$objDb->numRows)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }
        $objModel = CcCardealerModel::findById($objDb->id);


        // Add some fields
        $objForm->addFormField('name', array(
            'label'     => 'Name',
            'inputType' => 'text',
            'value'     => $objModel->name
        ));

        $objForm->addFormField('alias', array(
            'label'     => 'Alias',
            'inputType' => 'text',
            'value'     => $objModel->alias
        ));

        /**
         * $objForm->addFormField('ffm_partner_cat', array(
         * 'label'     => 'Kategorie',
         * 'inputType' => 'text',
         * 'value'     => $objModel->ffm_partner_cat
         * ));
         **/

        $objForm->addFormField('ffm_partner_filiale', array(
            'label'     => 'Filiale',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_filiale
        ));

        $objForm->addFormField('ffm_partner_strasse', array(
            'label'     => 'Strasse / Nr',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_strasse
        ));

        $objForm->addFormField('ffm_partner_plz', array(
            'label'     => 'PLZ',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_plz
        ));

        $objForm->addFormField('ffm_partner_ort', array(
            'label'     => 'Ort',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_ort
        ));

        $objForm->addFormField('ffm_partner_open', array(
            'label'     => 'Öffnungszeiten',
            'inputType' => 'formMultiText',
            'value'     => $objModel->ffm_partner_open
        ));

        $objForm->addFormField('ffm_partner_tel', array(
            'label'     => 'Telefon',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_tel
        ));

        $objForm->addFormField('ffm_partner_mail', array(
            'label'     => 'E-Mail',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_mail
        ));

        $objForm->addFormField('ffm_partner_www', array(
            'label'     => 'Webseite',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_www
        ));

        $objForm->addFormField('ffm_partner_www_linkText', array(
            'label'     => 'Linktext (Webseite)',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_www_linkText
        ));

        $objForm->addFormField('ffm_partner_facebook', array(
            'label'     => 'Social Facebook',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_facebook
        ));

        $objForm->addFormField('ffm_partner_twitter', array(
            'label'     => 'Social Twitter',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_twitter
        ));

        $objForm->addFormField('ffm_partner_instagram', array(
            'label'     => 'Social Instagram',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_instagram
        ));

        $objForm->addFormField('ffm_partner_google', array(
            'label'     => 'Social Google Plus',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_google
        ));

        $objForm->addFormField('ffm_partner_youtubeid', array(
            'label'     => 'Youtube ID',
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_youtubeid
        ));

        $objForm->addFormField('ffm_partner_text', array(
            'label'     => 'Beschreibung',
            'inputType' => 'textarea',
            'eval'      => array('preserveTags' => true, 'allowHtml' => true, 'decodeEntities' => true),
            'value'     => StringUtil::decodeEntities($objModel->ffm_partner_text)
        ));


        //die(print_r($objModel->row(),true));


        // Let's add  a submit button
        $objForm->addFormField('submit', array(
            'label'     => $GLOBALS['TL_LANG']['MSC']['memberPictureFeedUploadBtnlLabel'],
            'inputType' => 'submit',
        ));

        $objForm->bindModel($objModel);


        // Add attributes
        //$objWidgetFileupload = $objForm->getWidget('fileupload');
        //$objWidgetFileupload->addAttribute('accept', '.jpg, .jpeg');
        //$objWidgetFileupload->storeFile = true;

        if (Input::post('FORM_SUBMIT') != '')
        {
            if ($objForm->validate())
            {
                // Beschreibung use tinyMce
                // $objModel->ffm_partner_text = Input::postRaw('ffm_partner_text');
                $objModel->save();
                $this->reload();
            }
            else
            {

            }
        }

        $this->objForm = $objForm;
    }


}
