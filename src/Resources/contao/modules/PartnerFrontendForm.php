<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

namespace Markocupic\FrankfurterPartnerBundle\Contao\Modules;

use Contao\CcCardealerModel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Dbafs;
use Contao\Files;
use Contao\FilesModel;
use Contao\Folder;
use Contao\File;
use Contao\MemberGroupModel;
use Contao\Module;
use Contao\BackendTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;
use Markocupic\FrankfurterPartnerBundle\Contao\Classes\PartnerFrontendFormHelper;
use Patchwork\Utf8;
use Haste\Form\Form;
use Contao\Input;
use Contao\Environment;
use Contao\System;
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
    protected $Helper;

    /**
     * @var
     */
    public $objPartnerAbo;

    /**
     * @var
     */
    protected $textForm;

    /**
     * @var
     */
    protected $logoUploadForm;

    /**
     * @var
     */
    protected $mainImageUploadForm;

    /**
     * @var
     */
    protected $galleryUploadForm;

    /**
     * @var
     */
    protected $productUploadForm;

    /**
     * @var
     */
    protected $brandUploadForm;

    /**
     * @var
     */
    protected $memberBenefitForm;

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
     * @return string
     * @throws \Exception
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
            $this->objPartnerAbo = $this->getPartnerAbo();
        }
        else
        {
            return '';
        }

        // Initialize helper class
        $this->Helper = new PartnerFrontendFormHelper($this->objUser, $this->getPartnerModel());


        // Handle ajax requests
        if ((!is_array($_FILES) || empty($_FILES)) && Environment::get('isAjaxRequest'))
        {
            $this->handleAjaxRequest();
            exit();
        }

        // Set the preview token if it wasn't set already
        // cc_carreader.previewtoken for the preview module
        $this->setPreviewToken();

        return parent::generate();
    }


    /**
     * compile module
     */
    protected function compile()
    {

        $hasError = false;
        if (!$this->hasUploadDir())
        {
            $hasError = true;
            $this->arrMessages[] = sprintf($GLOBALS['TL_LANG']['ERR']['noUploadDirectoryDefined'], $this->objUser->firstname, $this->objUser->lastname);
        }
        elseif ($this->getPartnerModel() === false)
        {
            $hasError = true;
            $this->arrMessages[] = sprintf($GLOBALS['TL_LANG']['ERR']['noPartnerAssignedToThisUser'], $this->objUser->firstname, $this->objUser->lastname);
        }

        if (!$hasError)
        {
            // Generate all the forms
            $this->generateTextForm();
            $this->Template->textForm = $this->textForm;

            $this->generateLogoUploadForm();
            $this->Template->logoUploadForm = $this->logoUploadForm;

            $this->generateMainImageUploadForm();
            $this->Template->mainImageUploadForm = $this->mainImageUploadForm;

            $this->generateGalleryUploadForm();
            $this->Template->galleryUploadForm = $this->galleryUploadForm->generate();

            $this->generateProductUploadForm();
            $this->Template->productUploadForm = $this->productUploadForm;

            $this->generatebrandUploadForm();
            $this->Template->brandUploadForm = $this->brandUploadForm;

            if ($this->objPartnerAbo->allowMemberBenefitModule)
            {
                $this->generateMemberBenefitForm();
                $this->Template->memberBenefitForm = $this->memberBenefitForm;
            }


            // Add the preview page link
            if ($this->addPreviewPage && $this->previewPage > 0)
            {
                $objModel = $this->getPartnerModel();
                $objPage = PageModel::findByPk($this->previewPage);
                if ($objPage !== null)
                {
                    $this->Template->addPreviewPageLink = true;
                    $this->Template->objPreviewPage = $objPage;
                    $this->Template->objPreviewLinkUrl = $objPage->getFrontendUrl('/' . $objModel->alias) . '?previewtoken=' . $objModel->previewtoken;
                }
            }

            // Add the objPartnerAbo object to the template
            $this->Template->objPartnerAbo = $this->objPartnerAbo;

            // Assign helper class to the template
            $this->Template->Helper = $this->Helper;

        }


        // Handle Messages
        $session = System::getContainer()->get('session');
        // Get flash bag messages and merge them
        if ($session->isStarted() && $this->hasFlashMessage($this->flashMessageKey))
        {
            $this->arrMessages = array_merge($this->arrMessages, $this->getFlashMessage($this->flashMessageKey));
            $this->unsetFlashMessage($this->flashMessageKey);
        }

        // Add messages
        if (count($this->arrMessages) > 0)
        {
            $this->Template->hasMessages = true;
            $this->Template->messages = $this->arrMessages;
        }

        global $objPage;
        $this->Template->objPage = $objPage;


    }


    /**
     * @throws \Exception
     */
    protected function handleAjaxRequest()
    {

        // Ajax request: action=sortGallery
        if (Input::post('action') === 'sortGallery' && Input::post('arrUuid') != '')
        {

            $blnSuccess = 'error';
            $intItems = 0;
            $objModel = $this->getPartnerModel();

            if ($objModel !== null)
            {

                $arrUuid = json_decode(Input::post('arrUuid'));
                if (is_array($arrUuid))
                {
                    $uuids = array();
                    $intItems = count($arrUuid);
                    $i = 0;
                    foreach ($arrUuid as $hexUuid)
                    {
                        if (Validator::isStringUuid($hexUuid))
                        {
                            $uuid = StringUtil::uuidToBin($hexUuid);
                            $objFile = FilesModel::findByUuid($uuid);
                            if ($objFile !== null)
                            {
                                if (is_file(TL_ROOT . '/' . $objFile->path))
                                {
                                    $i++;
                                    $uuids[] = $uuid;
                                }
                            }
                        }
                    }

                    if ($i === $intItems)
                    {
                        $objModel->orderSRC_gallery = serialize($uuids);
                        $objModel->fetstamp = time();
                        $objModel->tstamp = time();
                        $objModel->save();
                        $blnSuccess = 'success';
                    }
                }
            }

            $arrJson = array('status' => $blnSuccess, 'items' => $i, 'intItems' => $intItems);
            echo \GuzzleHttp\json_encode($arrJson);
            exit();
        }


        // Ajax request: action=removeImage
        if (Input::post('action') === 'removeGalleryImage' && Input::post('fileId') != '')
        {
            $blnSuccess = 'error';
            $objModel = $this->getPartnerModel();

            if ($objModel !== null)
            {
                $objFile = FilesModel::findByPk(Input::post('fileId'));
                if ($objFile !== null)
                {
                    $oFile = new File($objFile->path);
                    if (is_file(TL_ROOT . '/' . $objFile->path))
                    {
                        $res = $objFile->path;
                        $uuid = $objFile->uuid;
                        $oFile->delete();
                        Dbafs::deleteResource($res);
                        Dbafs::updateFolderHashes(dirname($res));
                        $arrGallery = StringUtil::deserialize($objModel->gallery, true);
                        if (in_array($uuid, $arrGallery))
                        {
                            $key = array_search($uuid, $arrGallery);
                            unset($arrGallery[$key]);
                            $arrGallery = array_filter($arrGallery);
                            $objModel->gallery = serialize($arrGallery);
                            $objModel->fetstamp = time();
                            $objModel->tstamp = time();
                            $objModel->save();
                        }
                        $blnSuccess = 'success';
                    }
                }
            }

            $arrJson = array('status' => $blnSuccess, 'fileId' => Input::post('fileId'));
            echo \GuzzleHttp\json_encode($arrJson);
            exit();
        }

        if (Input::post('action') === 'removeImage' && Input::post('fileId') != '' && Input::post('fieldname') != '')
        {
            $blnSuccess = 'error';
            $objModel = $this->getPartnerModel();
            $fieldname = Input::post('fieldname');
            if ($objModel !== null)
            {
                $objFile = FilesModel::findByPk(Input::post('fileId'));
                if ($objFile !== null)
                {
                    $oFile = new File($objFile->path);
                    if (is_file(TL_ROOT . '/' . $objFile->path))
                    {
                        $strPath = $objFile->path;
                        $oFile->delete();
                        Dbafs::deleteResource($strPath);
                        Dbafs::updateFolderHashes(dirname($strPath));
                        $objModel->{$fieldname} = '';
                        $objModel->fetstamp = time();
                        $objModel->tstamp = time();
                        $objModel->save();
                        $blnSuccess = 'success';
                    }
                }
            }

            $arrJson = array('status' => $blnSuccess, 'fileId' => Input::post('fileId'));
            echo \GuzzleHttp\json_encode($arrJson);
            exit();
        }

        // Ajax request: action=rotateImage
        if (Input::post('action') === 'rotateImage' && Input::post('fileId') != '')
        {
            $blnSuccess = 'error';
            $objModel = $this->getPartnerModel();
            if ($objModel !== null)
            {
                $objFile = FilesModel::findByPk(Input::post('fileId'));
                if ($objFile !== null)
                {
                    if (true === $this->Helper->rotateImage($objFile->id))
                    {
                        $blnSuccess = 'success';
                        $objModel->fetstamp = time();
                        $objModel->tstamp = time();
                        $objModel->save();
                    }
                }
            }
            $arrJson = array('status' => $blnSuccess);
            echo \GuzzleHttp\json_encode($arrJson);
            exit();
        }

        exit();
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
            'label'     => $this->Helper->getCatalogAttributeTitle('name'),
            'inputType' => 'text',
            'value'     => $objModel->name
        ));

        $objForm->addFormField('alias', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('alias'),
            'inputType' => 'text',
            'value'     => $objModel->alias
        ));

        if ($this->objPartnerAbo->allowedMainCategories > 0)
        {
            $objForm->addFormField('hauptkategorie', array(
                'label'     => $this->Helper->getCatalogAttributeTitle('hauptkategorie'),
                'inputType' => 'select',
                'options'   => $this->Helper->getCatTags(),
                'eval'      => array('multiple' => false),
                'value'     => $objModel->hauptkategorie
            ));
        }

        if ($this->objPartnerAbo->allowedSubCategories > 0)
        {
            $objForm->addFormField('ffm_partner_cat', array(
                'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_cat'),
                'inputType' => 'checkbox',
                'options'   => $this->Helper->getCatTags(),
                'eval'      => array('multiple' => true),
                'value'     => $objModel->ffm_partner_cat
            ));
        }

        $objForm->addFormField('ffm_partner_filiale', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_filiale'),
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_filiale
        ));

        $objForm->addFormField('ffm_partner_strasse', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_strasse'),
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_strasse
        ));

        $objForm->addFormField('ffm_partner_plz', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_plz'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'natural'),
            'value'     => $objModel->ffm_partner_plz
        ));

        $objForm->addFormField('ffm_partner_ort', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_ort'),
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_ort
        ));

        $objForm->addFormField('ffm_partner_open', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_open'),
            'inputType' => 'formMultiText',
            'eval'      => array('multiple' => true),
            'value'     => $objModel->ffm_partner_open
        ));

        $objForm->addFormField('ffm_partner_tel', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_tel'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'phone'),
            'value'     => $objModel->ffm_partner_tel
        ));

        $objForm->addFormField('ffm_partner_mail', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_mail'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'email'),
            'value'     => $objModel->ffm_partner_mail
        ));

        $objForm->addFormField('ffm_partner_www', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_www'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'url'),
            'value'     => $objModel->ffm_partner_www
        ));

        $objForm->addFormField('ffm_partner_www_linkText', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_www_linkText'),
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_www_linkText
        ));

        $objForm->addFormField('ffm_partner_facebook', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_facebook'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'url'),
            'value'     => $objModel->ffm_partner_facebook
        ));

        $objForm->addFormField('ffm_partner_twitter', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_twitter'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'url'),
            'value'     => $objModel->ffm_partner_twitter
        ));

        $objForm->addFormField('ffm_partner_instagram', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_instagram'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'url'),
            'value'     => $objModel->ffm_partner_instagram
        ));

        $objForm->addFormField('ffm_partner_google', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_google'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'url'),
            'value'     => $objModel->ffm_partner_google
        ));

        if ($this->objPartnerAbo->allowYoutubeEmbed)
        {
            $objForm->addFormField('ffm_partner_youtubeid', array(
                'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_youtubeid'),
                'inputType' => 'text',
                'value'     => $objModel->ffm_partner_youtubeid
            ));
        }

        $objForm->addFormField('ffm_partner_text', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_text'),
            'inputType' => 'textarea',
            'eval'      => array('preserveTags' => true, 'allowHtml' => true, 'decodeEntities' => true),
            'value'     => StringUtil::decodeEntities(StringUtil::decodeEntities($objModel->ffm_partner_text))
        ));


        // Let's add  a submit button
        $objForm->addFormField('submit', array(
            'label'     => $GLOBALS['TL_LANG']['MSC']['partnerSaveBtnLabel'],
            'inputType' => 'submit',
        ));

        $objForm->bindModel($objModel);

        if ($objForm->validate())
        {
            $blnHasError = false;
            // Decode entities
            $objWidget = $objForm->getWidget('ffm_partner_text');
            if (!empty($GLOBALS['TL_CONFIG']['partnerAbo']['fields'][$objWidget->name]['eval']['maxlength']))
            {
                $intMaxLen = $GLOBALS['TL_CONFIG']['partnerAbo']['fields'][$objWidget->name]['eval']['maxlength'];
                $intStrLen = strlen(strip_tags(StringUtil::decodeEntities($objWidget->value)));
                if ($intStrLen > $intMaxLen)
                {
                    $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['partnerUploadStrToLong'], $intStrLen, $intMaxLen));
                    $blnHasError = true;
                }
            }

            $objModel->ffm_partner_text = StringUtil::decodeEntities($objWidget->value);
            //$objModel->ffm_partner_text = Input::postRaw('ffm_partner_text');

            // Set google maps fields
            $objModel->ffm_partner_map_street = $objModel->ffm_partner_strasse;
            $objModel->ffm_partner_map_city = $objModel->ffm_partner_ort;
            $objModel->ffm_partner_map_zipcode = $objModel->ffm_partner_plz;
            $objModel->ffm_partner_map = sprintf('%s, %s %s', $objModel->ffm_partner_strasse, $objModel->ffm_partner_plz, $objModel->ffm_partner_ort);

            // Validate Main Categories (Upload limit)
            $objWidget = $objForm->getWidget('hauptkategorie');
            if (!empty($objWidget->value))
            {
                if (is_array($objWidget->value))
                {
                    if (count($objWidget->value) > $this->objPartnerAbo->allowedMainCategories)
                    {
                        $blnHasError = true;
                        $objWidget->addError(sprintf($GLOBALS['TL_LANG']['MSC']['partnerUploadToManyMainCategoriesSelectedDuringUploadProcess'], $this->objPartnerAbo->allowedMainCategories));
                    }
                }
                else
                {
                    if ($this->objPartnerAbo->allowedMainCategories < 1)
                    {
                        $blnHasError = true;
                        $objWidget->addError(sprintf($GLOBALS['TL_LANG']['MSC']['partnerUploadToManyMainCategoriesSelectedDuringUploadProcess'], $this->objPartnerAbo->allowedMainCategories));
                    }
                }
            }

            // Validate Sub Categories (Upload limit)
            $objWidget = $objForm->getWidget('ffm_partner_cat');
            if (!empty($objWidget->value) && is_array($objWidget->value))
            {
                if (count($objWidget->value) > $this->objPartnerAbo->allowedSubCategories)
                {
                    $blnHasError = true;
                    $objWidget->addError(sprintf($GLOBALS['TL_LANG']['MSC']['partnerUploadToManySubCategoriesSelectedDuringUploadProcess'], $this->objPartnerAbo->allowedSubCategories));
                }
            }

            // Check if user has selected a valid hauptkategorie
            // hauptkategorie must be an item of ffm_partner_cat
            $objWidgetMultiCat = $objForm->getWidget('ffm_partner_cat');
            $objWidgetMainCat = $objForm->getWidget('hauptkategorie');
            if (!empty($objWidgetMainCat->value) && !is_array($objWidgetMainCat->value))
            {
                $err = false;
                if (empty($objWidgetMultiCat->value))
                {
                    $err = true;
                }
                elseif (!in_array($objWidgetMainCat->value, $objWidgetMultiCat->value))
                {
                    $err = true;
                }
                if ($err === true)
                {
                    $objWidgetMainCat->addError($GLOBALS['TL_LANG']['MSC']['partnerUploadInvalidMainCatSelected']);
                    $blnHasError = true;
                }
                unset($err);
            }


            if (!$blnHasError)
            {
                $objModel->fetstamp = time();
                $objModel->tstamp = time();

                // Save and reload
                $objModel->save();

                $this->reload();
            }

        }

        $this->textForm = $objForm;
    }


    /**
     * Generate the logo upload form
     */
    protected function generateLogoUploadForm()
    {

        $settings = array(
            'form'            => 'logoUploadForm',
            'uploadDir'       => 'logo_image',
            'uploadDirErrMsg' => 'Error! No valid upload folder for the logo image defined.',
            'formId'          => 'form-member-logo-upload',
            'uploadInputName' => 'ffm_partner_logo',
            'inputLabel'      => 'Logo',
            'filename'        => 'logo.%s',
        );
        $this->generateSingleUploadForm($settings);
    }


    /**
     * Generate the main image upload form
     */
    protected function generateMainImageUploadForm()
    {
        $settings = array(
            'form'            => 'mainImageUploadForm',
            'uploadDir'       => 'main_image',
            'uploadDirErrMsg' => 'Error! No valid upload folder for the main image defined.',
            'formId'          => 'form-member-main-image-upload',
            'uploadInputName' => 'image',
            'inputLabel'      => 'Hauptbild',
            'filename'        => 'main-image.%s',
        );
        $this->generateSingleUploadForm($settings);
    }


    /**
     * @return null
     */
    protected function generateGalleryUploadForm()
    {

        if ($this->objPartnerAbo->allowedGalleryImages > 0)
        {
            // Get the upload dir object
            if (false === $objUploadDir = $this->getUploadDirObject('gallery_images'))
            {
                $this->arrMessages[] = 'Error! No valid upload folder for the product images defined.';
                return;
            }

            // Create the form
            $objForm = $this->createForm('form-member-gallery-upload', 'multipart/form-data');

            // Get model
            $objModel = $this->getPartnerModel();

            // Add some fields
            $objForm->addFormField('gallery', array(
                'label'     => &$GLOBALS['TL_LANG']['MSC']['gallery-lbl'],
                'inputType' => 'fineUploader',
                'eval'      => array('extensions'   => 'jpg,jpeg',
                                     'storeFile'    => true,
                                     'addToDbafs'   => true,
                                     'isGallery'    => false,
                                     'directUpload' => false,
                                     'multiple'     => true,
                                     'useHomeDir'   => false,
                                     'uploadFolder' => $objUploadDir->path,
                                     'mandatory'    => false
                ),
            ));

            // Let's add  a submit button
            $objForm->addFormField('submit', array(
                'label'     => &$GLOBALS['TL_LANG']['MSC']['gallery-submit-btn'],
                'inputType' => 'submit',
            ));

            // Add attributes
            $objWidgetFileupload = $objForm->getWidget('gallery');
            $objWidgetFileupload->addAttribute('accept', '.jpg, .jpeg');
            $objWidgetFileupload->storeFile = true;

            // Overwrite uploader template
            if ($this->partnerFrontendFormFineuploaderTemplate !== '')
            {
                $objWidgetFileupload->template = $this->partnerFrontendFormFineuploaderTemplate;
            }


            // validate() also checks whether the form has been submitted
            if ($objForm->validate() && Input::post('FORM_SUBMIT') === $objForm->getFormId())
            {
                if (is_array($_SESSION['FILES']) && !empty($_SESSION['FILES']))
                {
                    $count = 0;
                    foreach ($_SESSION['FILES'] as $k => $file)
                    {
                        $count++;
                        $uuid = $file['uuid'];
                        if (Validator::isStringUuid($uuid))
                        {
                            $binUuid = StringUtil::uuidToBin($uuid);
                            $objFilesModel = FilesModel::findByUuid($binUuid);

                            if ($objFilesModel !== null)
                            {
                                $objFile = new File($objFilesModel->path);
                                //Check if upload limit is reached
                                if ($this->countGalleryImages($objModel) >= $this->objPartnerAbo->allowedGalleryImages && $this->objPartnerAbo->allowedGalleryImages > 0)
                                {
                                    // Delete from dbafs
                                    Dbafs::deleteResource($objFilesModel->path);
                                    // Delete from server
                                    $objFile->delete();
                                    Dbafs::updateFolderHashes($objUploadDir->path);
                                    $errMsg = sprintf($GLOBALS['TL_LANG']['MSC']['partnerUploadPictureUploadLimitReachedDuringUploadProcess'], $this->objPartnerAbo->allowedGalleryImages);
                                    $objWidgetFileupload->addError($errMsg);
                                    $this->setFlashMessage($this->flashMessageKey, $errMsg);
                                    unset($_SESSION['FILES']);
                                    $this->reload();
                                }
                                else
                                {
                                    // Rename file
                                    $newFilename = sprintf('%s-%s.%s', $this->objUser->id, time() . ($count), $objFile->extension);
                                    $newPath = dirname($objFile->path) . '/' . $newFilename;
                                    Files::getInstance()->rename($objFile->path, $newPath);
                                    Dbafs::addResource($newPath);
                                    Dbafs::deleteResource($objFilesModel->path);
                                    Dbafs::updateFolderHashes($objUploadDir->path);


                                    $objFilesModel = FilesModel::findByPath($newPath);
                                    $objGallery = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE id=?')->execute($objModel->id);
                                    if ($objGallery->numRows)
                                    {
                                        $arrGallery = StringUtil::deserialize($objGallery->gallery, true);
                                        $arrGallery[] = $objFilesModel->uuid;
                                        $arrSorting = StringUtil::deserialize($objGallery->orderSRC_gallery, true);
                                        $arrSorting = array_merge($arrSorting, $arrGallery);

                                        $set = array(
                                            'gallery'          => serialize($arrGallery),
                                            'orderSRC_gallery' => serialize($arrSorting),
                                            'fetstamp'         => time(),
                                            'tstamp'           => time()
                                        );
                                        Database::getInstance()->prepare("UPDATE cc_cardealer %s WHERE id=?")->set($set)->execute($objModel->id);
                                    }

                                    //$this->resizeUploadedImage($objModel->path);

                                    // Flash message
                                    //$this->setFlashMessage($this->flashMessageKey, sprintf($GLOBALS['TL_LANG']['MSC']['memberPictureFeedFileuploadSuccess'], $objModel->name));

                                    // Log
                                    $strText = sprintf('User with username %s has uploadad a new picture ("%s") via the partner upload form.', $this->objUser->username, $objFilesModel->path);
                                    $logger = System::getContainer()->get('monolog.logger.contao');
                                    $logger->log(LogLevel::INFO, $strText, array('contao' => new ContaoContext(__METHOD__, 'PARTNER_FRONTEND_FORM')));
                                }
                            }
                        }
                    }
                }

                if (!$objWidgetFileupload->hasErrors())
                {
                    // Reload page
                    $this->reload();
                }
            }

            unset($_SESSION['FILES']);
            $this->galleryUploadForm = $objForm;
        }

    }


    /**
     * Generate the product upload form
     */
    protected function generateProductUploadForm()
    {

        $allowedItems = $this->objPartnerAbo->allowedProducts;
        if ($allowedItems > 0)
        {
            // Get the upload dir object
            if (false === $objUploadDir = $this->getUploadDirObject('product_images'))
            {
                $this->arrMessages[] = 'Error! No valid upload folder for the product images defined.';
                return;
            }

            // Create the form
            $objForm = $this->createForm('form-member-product-upload', 'multipart/form-data');

            // Let's add  a submit button
            $fieldname = 'submit';
            $objForm->addFormField($fieldname, array(
                'label'     => &$GLOBALS['TL_LANG']['MSC']['partnerUploadAndSaveBtnLabel'],
                'inputType' => 'submit',
            ));

            // Get model
            $objModel = $this->getPartnerModel();

            // Bind model to the form
            $objForm->bindModel($objModel);

            $arrFileInputs = array();

            for ($intItem = 1; $intItem <= $allowedItems; $intItem++)
            {
                // Add leading zero: 01, 02, .... 12
                $strItem = str_pad($intItem, 2, '0', STR_PAD_LEFT);

                // Add some fields
                $strInputFileupload = 'ffm_partner_pro' . $strItem . '_img';
                $objForm->addFormField($strInputFileupload, array(
                    'label'     => $this->Helper->getCatalogAttributeTitle($strInputFileupload),
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

                $fieldname = 'produkt_' . $strItem . '_aktivieren';
                $objForm->addFormField($fieldname, array(
                    'label'     => array($this->Helper->getCatalogAttributeTitle($fieldname), 'Produkt anzeigen'),
                    'inputType' => 'checkbox',
                    'eval'      => array('multiple' => false),
                    'value'     => $objModel->{$fieldname}
                ));

                $fieldname = 'ffm_partner_pro' . $strItem . '_hl';
                $objForm->addFormField($fieldname, array(
                    'label'     => $this->Helper->getCatalogAttributeTitle($fieldname),
                    'inputType' => 'text',
                    'value'     => $objModel->{$fieldname}
                ));

                $fieldname = 'ffm_partner_pro' . $strItem . '_lab';
                $objForm->addFormField($fieldname, array(
                    'label'     => $this->Helper->getCatalogAttributeTitle($fieldname),
                    'inputType' => 'text',
                    'value'     => $objModel->{$fieldname}
                ));

                $fieldname = 'ffm_partner_pro' . $strItem . '_link';
                $objForm->addFormField($fieldname, array(
                    'label'     => $this->Helper->getCatalogAttributeTitle($fieldname),
                    'inputType' => 'text',
                    'eval'      => array('rgxp' => 'url'),
                    'value'     => $objModel->{$fieldname}
                ));

                $arrFileInputs[] = array(
                    'intItem'   => $intItem,
                    'inputName' => $strInputFileupload,
                    'hasUpload' => $hasUpload
                );
            } // end for

            // Validate
            if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
            {
                if ($objForm->validate())
                {
                    $objModel->fetstamp = time();
                    $objModel->tstamp = time();
                    $objModel->save();

                    // Traverse each file input
                    foreach ($arrFileInputs as $fileInput)
                    {
                        if ($fileInput['hasUpload'])
                        {
                            $this->validateFileUpload($objModel, $fileInput['inputName']);
                        }
                    }

                    $this->reload();
                }
            }
            $this->productUploadForm = $objForm;
        }
    }


    /**
     * Generate the product upload forms
     */
    protected function generateBrandUploadForm()
    {
        $allowedItems = $this->objPartnerAbo->allowedImagesOurBrands;

        if ($allowedItems > 0)
        {
            // Get the upload dir object
            if (false === $objUploadDir = $this->getUploadDirObject('product_images'))
            {
                $this->arrMessages[] = 'Error! No valid upload folder for the product images defined.';
                return;
            }

            // Create the form
            $objForm = $this->createForm('form-member-brand-upload', 'multipart/form-data');

            // Let's add  a submit button
            $fieldname = 'submit';
            $objForm->addFormField($fieldname, array(
                'label'     => &$GLOBALS['TL_LANG']['MSC']['partnerUploadAndSaveBtnLabel'],
                'inputType' => 'submit',
            ));

            // Get model
            $objModel = $this->getPartnerModel();

            // Bind model to the form
            $objForm->bindModel($objModel);

            $arrFileInputs = array();

            for ($intItem = 1; $intItem <= $allowedItems; $intItem++)
            {
                // Add leading zero: 01, 02, .... 12
                $strItem = str_pad($intItem, 2, '0', STR_PAD_LEFT);

                // Add some fields
                $strInputFileupload = 'ffm_partner_lab' . $strItem . '_img';
                $objForm->addFormField($strInputFileupload, array(
                    'label'     => $this->Helper->getCatalogAttributeTitle($strInputFileupload),
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
                    $hasUpload = $this->renameFileInGlobals($objForm, $strInputFileupload, 'brand-image-' . $strItem . '.%s');
                }

                $arrFileInputs[] = array(
                    'intItem'   => $intItem,
                    'inputName' => $strInputFileupload,
                    'hasUpload' => $hasUpload
                );
            }//endfor

            // Validate
            if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
            {
                if ($objForm->validate())
                {
                    $objModel->fetstamp = time();
                    $objModel->tstamp = time();
                    $objModel->save();

                    // Traverse each file input
                    foreach ($arrFileInputs as $fileInput)
                    {
                        if ($fileInput['hasUpload'])
                        {
                            $this->validateFileUpload($objModel, $fileInput['inputName']);
                        }
                    }

                    $this->reload();
                }
            }
            $this->brandUploadForm = $objForm;
        }

    }


    /**
     * Generate the member benefit form
     */
    protected function generateMemberBenefitForm()
    {

        // Get model
        $objModel = $this->getPartnerModel();

        // Create the form
        $objForm = $this->createForm('form-member-benefit-form', 'application/x-www-form-urlencoded');

        $objForm->addFormField('ffm_partner_memberBenefitPublish', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_memberBenefitPublish'),
            'inputType' => 'checkbox',
            'options'   => array('1' => 'aktivieren/veröffentlichen'),
            'value'     => $objModel->ffm_partner_memberBenefitPublish
        ));

        $objForm->addFormField('ffm_partner_memberBenefitHeadline', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_memberBenefitHeadline'),
            'inputType' => 'text',
            'value'     => $objModel->ffm_partner_memberBenefitHeadline
        ));

        $objForm->addFormField('ffm_partner_memberBenefitText', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_memberBenefitText'),
            'inputType' => 'textarea',
            'eval'      => array('preserveTags' => true, 'allowHtml' => true, 'decodeEntities' => true),
            'value'     => StringUtil::decodeEntities(StringUtil::decodeEntities($objModel->ffm_partner_memberBenefitText))
        ));

        $objForm->addFormField('ffm_partner_memberBenefitEmail', array(
            'label'     => $this->Helper->getCatalogAttributeTitle('ffm_partner_memberBenefitEmail'),
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'email'),
            'value'     => $objModel->ffm_partner_memberBenefitEmail
        ));

        $objForm->addFormField('submit', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['partnerSaveBtnLabel'],
            'inputType' => 'submit',
        ));

        // Bind model to the form
        $objForm->bindModel($objModel);

        // Validate
        if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
        {
            if ($objForm->validate())
            {
                $blnHasError = false;
                // Decode entities
                $objWidget = $objForm->getWidget('ffm_partner_memberBenefitText');
                if (!empty($GLOBALS['TL_CONFIG']['partnerAbo']['fields'][$objWidget->name]['eval']['maxlength']))
                {
                    $intMaxLen = $GLOBALS['TL_CONFIG']['partnerAbo']['fields'][$objWidget->name]['eval']['maxlength'];
                    $intStrLen = strlen(strip_tags(StringUtil::decodeEntities($objWidget->value)));
                    if ($intStrLen > $intMaxLen)
                    {
                        $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['partnerUploadStrToLong'], $intStrLen, $intMaxLen));
                        $blnHasError = true;
                    }
                }

                if ($blnHasError === false)
                {
                    $objModel->fetstamp = time();
                    $objModel->tstamp = time();
                    $objModel->save();

                    $this->reload();
                }
            }
        }
        $this->memberBenefitForm = $objForm;

    }


    /**
     * @param $settings
     * @throws \Exception
     */
    protected function generateSingleUploadForm($settings)
    {
        // Get the upload dir object
        if (false === $objUploadDir = $this->getUploadDirObject($settings['uploadDir']))
        {
            $this->arrMessages[] = $settings['uploadDirErrMsg'];
            return;
        }

        // Create the form
        $objForm = $this->createForm($settings['formId'], 'multipart/form-data');

        // Get model
        $objModel = $this->getPartnerModel();


        // Add some fields
        $strInputFileupload = $settings['uploadInputName'];
        $objForm->addFormField($strInputFileupload, array(
            'label'     => $settings['inputLabel'],
            'inputType' => 'upload',
            'eval'      => array(
                'uploadFolder' => $objUploadDir->uuid,
                'storeFile'    => true,
                'extensions'   => 'jpg,png,gif,jpeg')
        ));
        // !!!Put this right after the widget dca settings
        // Rename the uploaded file in $_FILES before the validation process
        $hasUpload = false;
        if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
        {
            $hasUpload = $this->renameFileInGlobals($objForm, $strInputFileupload, $settings['filename']);
        }

        // Let's add  a submit button
        $fieldname = 'submit';
        $objForm->addFormField($fieldname, array(
            'label'     => $GLOBALS['TL_LANG']['MSC']['partnerUploadBtnLabel'],
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
                    if ($hasUpload)
                    {
                        $this->validateFileUpload($objModel, $strInputFileupload);
                    }
                }

                $this->reload();
            }
        }

        $this->{$settings['form']} = $objForm;
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
        if ($objDb->numRows)
        {
            return CcCardealerModel::findById($objDb->id);
        }
        // throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        return false;
    }


    /**
     * @return null|\stdClass
     */
    protected function getPartnerAbo()
    {
        if (FE_USER_LOGGED_IN)
        {
            $objUser = FrontendUser::getInstance();
            if ($objUser !== null)
            {
                $groupsUserBelongsTo = StringUtil::deserialize($objUser->groups, true);
                if (!empty($groupsUserBelongsTo))
                {
                    foreach ($groupsUserBelongsTo as $groupId)
                    {
                        $objGroup = MemberGroupModel::findByPk($groupId);
                        if ($objGroup !== null)
                        {
                            if ($objGroup->hasPartnerAbo)
                            {
                                if ($objGroup->partnerAbo != '')
                                {
                                    if (is_array($GLOBALS['TL_CONFIG']['partnerAbos']))
                                    {
                                        $partnerObject = new \stdClass();
                                        $partnerObject->aboName = $objGroup->partnerAbo;
                                        $partnerObject->aboNameTranslation = $GLOBALS['TL_LANG']['MSC'][$objGroup->partnerAbo];
                                        $partnerObject->allowedGalleryImages = $GLOBALS['TL_CONFIG']['partnerAboAllowedGalleryImages'][$objGroup->partnerAbo];
                                        $partnerObject->allowedMainCategories = $GLOBALS['TL_CONFIG']['partnerAboAllowedMainCategories'][$objGroup->partnerAbo];
                                        $partnerObject->allowedSubCategories = $GLOBALS['TL_CONFIG']['partnerAboAllowedSubCategories'][$objGroup->partnerAbo];
                                        $partnerObject->allowedImagesOurBrands = $GLOBALS['TL_CONFIG']['partnerAboAllowedImagesOurBrands'][$objGroup->partnerAbo];
                                        $partnerObject->allowedProducts = $GLOBALS['TL_CONFIG']['partnerAboAllowedProducts'][$objGroup->partnerAbo];
                                        $partnerObject->allowYoutubeEmbed = $GLOBALS['TL_CONFIG']['partnerAboAllowYoutubeEmbed'][$objGroup->partnerAbo];
                                        $partnerObject->allowMemberBenefitModule = $GLOBALS['TL_CONFIG']['partnerAboAllowMemberBenefitModule'][$objGroup->partnerAbo];
                                        return $partnerObject;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }


    /**
     * @return bool
     */
    protected function hasUploadDir()
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
                            return true;
                        }
                    }
                }
            }
        }

        return false;
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
                            $_FILES[$strFieldname]['name'] = sprintf($strNewName, strtolower($objFile->extension));
                            return true;
                        }
                    }
                }
            }
            return false;
        }
    }


    /**
     * @param $objModel
     * @param $strInputFileupload
     */
    protected function validateFileupload($objModel, $strInputFileupload)
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
                            $strInputFileupload => $objFile->uuid,
                            'fetstamp'          => time(),
                            'tstamp'            => time()
                        );
                        Database::getInstance()->prepare('UPDATE cc_cardealer %s WHERE id=?')->set($set)->execute($objModel->id);
                    }
                }
            }
        }
    }


    /**
     * @param $objModel
     * @return int|null
     */
    protected function countGalleryImages($objModel)
    {
        $objDb = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE id=?')->execute($objModel->id);
        if ($objDb->numRows)
        {
            $arrGallery = StringUtil::deserialize($objDb->gallery, true);
            return count($arrGallery);
        }
        return null;
    }


    /**
     *
     */
    protected function setPreviewToken()
    {
        $objModel = $this->getPartnerModel();
        if ($objModel !== null)
        {
            if (trim($objModel->previewtoken === ''))
            {
                $objModel->previewtoken = sha1(rand(435345, 98098908908) . time()) . $objModel->id;
                $objModel->save();
            }
        }
    }

    /**
     * @param $key
     * @param string $message
     */
    protected function setFlashMessage($key, $message = '')
    {
        if (!isset($_SESSION[$key]))
        {
            $_SESSION[$key] = array();

        }
        $_SESSION[$key][] = $message;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getFlashMessage($key)
    {
        if (!isset($_SESSION[$key]))
        {
            $_SESSION[$key] = array();

        }
        return $_SESSION[$key];
    }

    /**
     * @param $key
     */
    protected function unsetFlashMessage($key)
    {
        if (isset($_SESSION[$key]))
        {
            $_SESSION[$key] = null;
            unset($_SESSION[$key]);
        }
    }

    /**
     * @param $key
     * @return bool
     */
    protected function hasFlashMessage($key)
    {
        if (isset($_SESSION[$key]) && !empty($_SESSION[$key]))
        {
            return true;
        }
        return false;
    }
}
