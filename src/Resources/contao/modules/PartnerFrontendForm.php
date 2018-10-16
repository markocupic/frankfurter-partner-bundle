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

        $objModel= CcCardealerModel::findById(150);
        $value =  $objModel->ffm_partner_open;

        // Add some fields
        $objForm->addFormField('ffm_partner_open', array(
            'label'     => 'Öffnungszeiten',
            'inputType' => 'formMultiText',
            'value' => $value
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

        if(Input::post('FORM_SUBMIT') != '')
        {
            if($objForm->validate())
            {
                if(Input::post('ffm_partner_open') != '')
                {
                    //$objModel->ffm_partner_open = serialize(Input::post('ffm_partner_open'));
                    $objModel->save();

                }
                $this->reload();
            }else{
            }
        }

        $this->objForm = $objForm->generate();
    }







}
