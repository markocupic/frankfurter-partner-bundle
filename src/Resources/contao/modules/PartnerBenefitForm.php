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
use Contao\MemberModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;
use Markocupic\FrankfurterPartnerBundle\Contao\Classes\PartnerFrontendFormHelper;
use NotificationCenter\Model\Notification;
use Patchwork\Utf8;
use Haste\Form\Form;
use Contao\Input;
use Contao\Environment;
use Contao\System;
use Psr\Log\LogLevel;


/**
 * Class PartnerBenefitForm
 * @package Markocupic\FrankfurterPartnerBundle\Contao\Modules
 */
class PartnerBenefitForm extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_partnerBenefitForm';

    /**
     * @var
     */
    protected $objPartnerModel;

    /**
     * @var
     */
    protected $objPartnerAbo;

    /**
     * @var
     */
    protected $form;


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

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['partnerBenefitForm'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // set GET parameter
        if (!isset($_GET[$GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter']]) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
        {
            Input::setGet($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter'], Input::get('auto_item'));
        }

        // Get model
        if (null === ($this->objPartnerModel = $this->getPartnerModelByAlias(Input::get($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter']))))
        {
            return '';
        }

        // Get partner abo
        if (null === ($this->objPartnerAbo = $this->getPartnerAbo()))
        {
            return '';
        }

        // Is allowed to use the service
        if ('1' !== $this->objPartnerModel->ffm_partner_memberBenefitPublish)
        {
            return '';
        }

        // Is allowed to use the service
        if (false === $this->objPartnerAbo->allowMemberBenefitModule)
        {
            return '';
        }

        return parent::generate();
    }


    /**
     * compile module
     */
    protected function compile()
    {

        // Generate all the forms
        $this->generateForm();
        $this->Template->form = $this->form;
        $this->Template->objPartnerModel = $this->objPartnerModel;

    }





    /**
     *
     */
    protected function generateForm()
    {
        $blnError = false;

        // Create the form
        $objForm = new Form('form-member-benefit-modal-form', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $url = Environment::get('uri');
        $objForm->setFormActionFromUri($url);

        // Add hidden fields REQUEST_TOKEN & FORM_SUBMIT
        $objForm->addContaoHiddenFields();
        $arrVisibleFields = array(
            'memberBenefitFirstname',
            'memberBenefitLastname',
            'memberBenefitPhone',
            'memberBenefitEmail',
        );

        // Add some fields
        $objForm->addFormField('memberBenefitFirstname', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['memberBenefitFirstname'][0],
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));

        $objForm->addFormField('memberBenefitLastname', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['memberBenefitLastname'][0],
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));

        $objForm->addFormField('memberBenefitEmail', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['memberBenefitEmail'][0],
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'rgxp' => 'email')
        ));

        $objForm->addFormField('memberBenefitPhone', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['memberBenefitPhone'][0],
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'rgxp' => 'phone')
        ));

        $objForm->addFormField('memberBenefitSubmit', array(
            'label'     => &$GLOBALS['TL_LANG']['MSC']['memberBenefitSubmit'][0],
            'inputType' => 'submit',
        ));

        if (Input::post('FORM_SUBMIT') === $objForm->getFormId())
        {
            // Send ajax response
            if (Environment::get('isAjaxRequest'))
            {
                $json = array();
                $json['state'] = 'error';
                $json['arrNotification'] = array();
                if ($objForm->validate())
                {
                    $json['state'] = 'success';
                    $json['arrNotification'] = $this->sendNotification($objForm);
                }
                $json['fields'] = array();
                foreach ($arrVisibleFields as $field)
                {
                    $json['fields'][$field] = $objForm->getWidget($field)->parse();
                }
                echo json_encode($json);
                exit();
            }
            else
            {
                $blnError = true;
            }
        }

        if ($blnError === false)
        {
            $this->form = $objForm;
        }
    }

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function sendNotification($objForm)
    {
        global $objPage;

        $strType = 'member_benefit_advice';
        $objNotificationCollection = Notification::findByType($strType);
        $arrNotification = array();
        if (null !== $objNotificationCollection)
        {
            while ($objNotificationCollection->next())
            {

                $objNotification = $objNotificationCollection->current();

                // Set token array
                $arrTokens = array(
                    'partner_email' => $this->objPartnerModel->ffm_partner_memberBenefitEmail,
                    'partner_name'  => html_entity_decode($this->objPartnerModel->name),
                    'partner_id'    => $this->objPartnerModel->id,
                    'partner_alias' => html_entity_decode($this->objPartnerModel->alias),
                );
                if ($objForm->hasFormField('memberBenefitFirstname'))
                {
                    $arrTokens['customer_firstname'] = $objForm->getWidget('memberBenefitFirstname')->value;
                }
                if ($objForm->hasFormField('memberBenefitLastname'))
                {
                    $arrTokens['customer_lastname'] = $objForm->getWidget('memberBenefitLastname')->value;
                }
                if ($objForm->hasFormField('memberBenefitPhone'))
                {
                    $arrTokens['customer_phone'] = $objForm->getWidget('memberBenefitPhone')->value;
                }
                if ($objForm->hasFormField('memberBenefitEmail'))
                {
                    $arrTokens['customer_email'] = $objForm->getWidget('memberBenefitEmail')->value;
                }

               $arrNotification[] = $objNotification->send($arrTokens, $objPage->language); // Language is optional
            }
        }

        return $arrNotification;
    }

    /**
     * @param string $strAlias
     * @return null
     */
    protected function getPartnerModelByAlias($strAlias = '')
    {
        if ($strAlias === '')
        {
            return null;
        }
        $objDb = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE alias=?')->limit(1)->execute($strAlias);
        if ($objDb->numRows)
        {
            return CcCardealerModel::findById($objDb->id);
        }

        return null;
    }

    /**
     * @return null|\stdClass
     */
    protected function getPartnerAbo()
    {

        if ($this->objPartnerModel !== null)
        {
            $objUser = MemberModel::findByPk($this->objPartnerModel->memberid);
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

}
