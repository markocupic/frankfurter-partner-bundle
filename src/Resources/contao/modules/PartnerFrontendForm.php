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
    protected $textForm;

    /**
     * @var array
     */
    protected $productUploadForms = array();

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
        $this->generateTextForm();
        $this->Template->textForm = $this->textForm;

        $this->generateProductUploadForms();
        $this->Template->productUploadForms = $this->productUploadForms;
    }

    /**
     * Generate the product upload forms
     */
    protected function generateProductUploadForms()
    {
        $allowedItems = 12;

        // Get the upload dir object
        if (false === $objUploadDir = $this->getUploadDirObject('product_images'))
        {
            $this->arrMessages[] = 'Error! No valid upload folder for the product images defined.';
            return;
        }


        for ($intItem = 1; $intItem <= $allowedItems; $intItem++)
        {

            // Add leading zero: 01, 02, .... 12
            $strItem = str_pad($intItem, 2, '0', STR_PAD_LEFT);

            // Create the form
            $objForm = $this->createForm('form-member-product-upload-' . $strItem, 'multipart/form-data');

            // Get model
            $objModel = $this->getPartnerModel();


            // Add some fields
            $strInputFileupload = 'ffm_partner_pro' . $strItem . '_img';
            $objForm->addFormField($strInputFileupload, array(
                'label'     => 'Bild zu Produkt ' . $strItem,
                'inputType' => 'upload',
                'eval'      => array(
                    'uploadFolder' => $objUploadDir->uuid,
                    'storeFile'    => true,
                    'extensions'   => 'jpg,png,gif,jpeg'
                    //'value'     => $objModel->{$fieldname}
                )
            ));
            // !!!Put this right after the widget dca settings
            // Rename the uploaded file in $_FILES before the validation process
            $hasUpload = false;
            if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
            {
                $hasUpload = $this->renameFileInGlobals($objForm, $strInputFileupload, 'product-image-' . $strItem . '.%s');
            }

            $fieldname = 'ffm_partner_pro' . $strItem . '_hl';
            $objForm->addFormField($fieldname, array(
                'label'     => 'Produkt ' . $strItem . ' Titel',
                'inputType' => 'text',
                'value'     => $objModel->{$fieldname}
            ));

            $fieldname = 'ffm_partner_pro' . $strItem . '_lab';
            $objForm->addFormField($fieldname, array(
                'label'     => 'Produkt ' . $strItem . ' Label',
                'inputType' => 'text',
                'value'     => $objModel->{$fieldname}
            ));

            $fieldname = 'ffm_partner_pro' . $strItem . '_link';
            $objForm->addFormField($fieldname, array(
                'label'     => 'Produkt ' . $strItem . ' Link',
                'inputType' => 'text',
                'value'     => $objModel->{$fieldname}
            ));

            // Let's add  a submit button
            $fieldname = 'submit';
            $objForm->addFormField($fieldname, array(
                'label'     => $GLOBALS['TL_LANG']['MSC']['memberPictureFeedUploadBtnlLabel'],
                'inputType' => 'submit',
            ));

            // Bind model to the form
            $objForm->bindModel($objModel);


            // Validate
            if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
            {
                if ($objForm->validate())
                {
                    if ($hasUpload)
                    {
                        if (is_array($_SESSION['FILES'][$strInputFileupload]) && !empty($_SESSION['FILES'][$strInputFileupload]))
                        {
                            $strUuid = $_SESSION['FILES'][$strInputFileupload]['uuid'];
                            if (Validator::isStringUuid($strUuid))
                            {
                                $uuid = StringUtil::uuidToBin($strUuid);
                                $objFile = FilesModel::findByUuid($uuid);
                                if ($objFile !== null)
                                {
                                    if (is_file(TL_ROOT . '/' . $objFile->path))
                                    {
                                        $set = array(
                                            $strInputFileupload                        => $objFile->uuid,
                                            sprintf('produkt_%s_aktivieren', $strItem) => '1'
                                        );
                                        Database::getInstance()->prepare('UPDATE cc_cardealer %s WHERE id=?')->set($set)->execute($objModel->id);
                                    }
                                }
                            }
                        }
                    }


                    $this->reload();
                }
            }


            $this->productUploadForms[] = $objForm;


        } // end for
    }

    /**
     *
     */
    protected function generateTextForm()
    {

        // Create the form
        $objForm = $this->createForm('form-member-text-form');


        // Add hidden fields REQUEST_TOKEN & FORM_SUBMIT
        $objForm->addContaoHiddenFields();

        // Get model
        $objModel = $this->getPartnerModel();


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

        $this->textForm = $objForm;
    }

    /**
     * @param $strId
     * @return Form
     */
    protected function createForm($strId)
    {

        $objForm = new Form($strId, 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });


        $url = Environment::get('uri');
        $objForm->setFormActionFromUri($url);

        // Add hidden fields REQUEST_TOKEN & FORM_SUBMIT
        $objForm->addContaoHiddenFields();

        return $objForm;
    }

    /**
     * @return mixed
     */
    protected function getPartnerModel()
    {
        $objDb = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE memberid=?')->limit(1)->execute($this->objUser->id);
        if (!$objDb->numRows)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }
        return CcCardealerModel::findById($objDb->id);
    }

    /**
     * @param $strSubDir
     * @return bool|FilesModel|null
     * @throws \Exception
     */
    protected function getUploadDirObject($strSubDir)
    {

        if ($this->objUser->assignDir)
        {
            if ($this->objUser->homeDir !== '')
            {
                if (Validator::isBinaryUuid($this->objUser->homeDir))
                {
                    $objFile = FilesModel::findByUuid($this->objUser->homeDir);
                    if ($objFile->type === 'folder')
                    {
                        if (is_dir(TL_ROOT . '/' . $objFile->path))
                        {
                            new Folder($objFile->path . '/' . $strSubDir);
                            Dbafs::addResource($objFile->path . '/' . $strSubDir);
                            $objUploadDir = FilesModel::findByPath($objFile->path . '/' . $strSubDir);
                            if (is_dir(TL_ROOT . '/' . $objFile->path . '/' . $strSubDir) && $objUploadDir !== null)
                            {
                                return $objUploadDir;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Rename uploaded file before the validation
     * @param $objForm
     * @param $strFieldname
     * @param $strNewName
     * @return bool
     * @throws \Exception
     */
    protected function renameFileInGlobals($objForm, $strFieldname, $strNewName)
    {
        if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
        {
            if (is_array($_FILES) && !empty($_FILES))
            {

                if (is_array($_FILES[$strFieldname]))
                {
                    if ($_FILES[$strFieldname]['name'] !== '')
                    {
                        $objFile = new File($_FILES[$strFieldname]['name']);
                        if ($objFile !== null)
                        {
                            $_FILES[$strFieldname]['name'] = sprintf($strNewName, $objFile->extension);
                            return true;
                        }
                    }
                }
            }
            return false;
        }
    }

}
