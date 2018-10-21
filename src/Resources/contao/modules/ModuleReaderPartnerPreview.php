<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

namespace PCT\CustomElements\Plugins\CustomCatalog\Frontend;

/**
 * Imports
 */
use Contao\CcCardealerModel;
use Contao\Database;
use Patchwork\Utf8;
use PCT\CustomElements\Plugins\CustomCatalog\Core\CustomCatalogFactory as CustomCatalogFactory;
use PCT\CustomElements\FilterFactory as FilterFactory;

/**
 * Class file
 * ModuleReader
 */
class ModuleReaderPartnerPreview extends \Module
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_customcatalog';

    /**
     * Flag if the reader should render content elements
     */
    protected $blnRenderContentElements = false;


    /**
     * Display wildcard
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = $strWildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['customcatalogreader'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;

            return $objTemplate->parse();
        }

        // set template
        if ($this->customcatalog_mod_template != $this->strTemplate)
        {
            $this->strTemplate = $this->customcatalog_mod_template;
        }

        // set GET parameter
        if (!isset($_GET[$GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter']]) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
        {
            \Input::setGet($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter'], \Input::get('auto_item'));
        }

        // allow tl_content
        if (\Input::get('table') == 'tl_content')
        {
            \Input::setGet('table', \Input::get('table'));
            \Input::setGet('pid', \Input::get('pid'));

            \Input::setGet($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter'], \Input::get('pid'));

            $this->blnRenderContentElements = true;
        }
        // return and do not index the page when there is no entry
        else if (!\Input::get($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter']) || !$this->customcatalog || strlen($this->customcatalog) < 1)
        {
            global $objPage;
            $objPage->noSearch = 1;
            $objPage->cache = 0;
            return '';
        }

        return parent::generate();
    }

    /**
     * @var Hack Marko Cupic 21.10.2018
     * @return mixed
     */
    public function getPartnerModel()
    {
        $alias = $_GET['items'];
        $objDb = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE alias=?')->limit(1)->execute($alias);
        if (!$objDb->numRows)
        {
            die('Page not found exception in ' . __METHOD__ . 'on line ' . __LINE__);
        }
        return CcCardealerModel::findById($objDb->id);
    }


    /**
     * Generate the module
     * @return string
     */
    protected function compile()
    {
        $objCC = CustomCatalogFactory::findByModule($this->objModel);
        if (!$objCC)
        {
            return '';
        }


        // fill the cache
        \PCT\CustomElements\Plugins\CustomCatalog\Core\SystemIntegration::fillCache($objCC->id);

        $strLanguage = '';
        if ($objCC->get('multilanguage') && ($this->customcatalog_filter_actLang || $objCC->get('aliasField') > 0))
        {
            $objMultilanguage = new \PCT\CustomElements\Plugins\CustomCatalog\Core\Multilanguage;
            $strLanguage = $objMultilanguage->getActiveFrontendLanguage();
        }

        /** @var Hack Marko Cupic 21.10.2018 * */
        $blnChangedPublishState = false;
        $objModel = $this->getPartnerModel();
        if (\Input::get('previewtoken') != '' && \Input::get('previewtoken') === $objModel->previewtoken)
        {
            if (!$objModel->publish)
            {

                $blnChangedPublishState = true;
                $objModel->publish = '1';
                $objModel->save();
            }
        }
        /** @var End Hack Marko Cupic 21.10.2018 * */


        // render the regular details page of a customcatalog entry
        $objEntry = $objCC->findPublishedItemByIdOrAlias(\Input::get($GLOBALS['PCT_CUSTOMCATALOG']['urlItemsParameter']), $strLanguage);

        //die(print_r($objEntry,true));

        // show 404 if entry does not exist
        if ($objEntry->id < 1)
        {
            global $objPage;
            $objPage->noSearch = 1;
            $objPage->cache = 0;

            // throw a page not found exception
            if (version_compare(VERSION, '4', '>='))
            {
                throw new \Contao\CoreBundle\Exception\PageNotFoundException('Page not found: ' . \Environment::get('uri'));
            }
            else
            {
                /** @var \PageError404 $objHandler */
                $objHandler = new $GLOBALS['TL_PTY']['error_404']();
                $objHandler->generate($objPage->id);
            }
            return '';
        }

        // prepare the filter. Just filter by the id of the entry
        $objFilter = new \PCT\CustomElements\Filters\SimpleFilter(array($objEntry->id));

        // set the filter
        $objCC->setFilter($objFilter);

        // total number of items
        $intTotal = $objCC->getTotal();

        // render / generate custom catalog
        $this->Template->customcatalog = $objCC->render();

        // append content elements
        if ($this->blnRenderContentElements)
        {

            $objEntry = $objCC->findPublishedItemByIdOrAlias(\Input::get('pid'), $strLanguage);

            $objEntries = $objCC->fetchPublishedContentElementsById($objEntry->id);

            // total number of items
            $intTotal = $objEntries->numRows;

            // render the content elements
            $strContent = $objCC->renderContentElements($objEntries);

            $this->Template->tl_content .= $strContent;
            $this->Template->customcatalog .= $strContent;
        }

        // comments
        if ($objCC->get('allowComments') > 0 && in_array('comments', \ModuleLoader::getActive()))
        {
            // Adjust the comments headline level
            $intHl = min(intval(str_replace('h', '', $this->hl)), 5);
            $this->Template->hlc = 'h' . ($intHl + 1);

            $this->Template->allowComments = true;
            $objComments = new \Contao\Comments();

            // Notify the system administrator
            $arrNotifies = array($GLOBALS['TL_ADMIN_EMAIL']);

            // Notify a different person
            if (strlen($objCC->get('com_notify')) > 0 && $objCC->get('com_notify') != 'notify_admin')
            {
                $arrNotifies = array($objCC->get('com_notify'));
            }

            $objConfig = new \stdClass();
            $objConfig->perPage = $objCC->get('com_perPage');
            $objConfig->order = $objCC->get('com_sortOrder');
            $objConfig->template = $this->com_template;
            $objConfig->requireLogin = $objCC->get('com_requireLogin');
            $objConfig->disableCaptcha = $objCC->get('com_disableCaptcha');
            $objConfig->bbcode = $objCC->get('com_bbcode');
            $objConfig->moderate = $objCC->get('com_moderate');

            $intParent = $objEntry->id;
            if ($intParent < 1)
            {
                $intParent = $objCC->get('id');
            }

            // backdoor and fallback
            if (isset($GLOBALS['PCT_CUSTOMCATALOG']['SETTINGS'][$objCC->getTable()]['uniqueComments']) && $GLOBALS['PCT_CUSTOMCATALOG']['SETTINGS'][$objCC->getTable()]['uniqueComments'] === false)
            {
                $intParent = $objCC->get('id');
            }

            $objComments->addCommentsToTemplate($this->Template, $objConfig, $objCC->getTable(), $intParent, $arrNotifies);
        }

        // readerpagination
        $this->Template->pagination = '';

        $arrCssID = deserialize($this->cssID);
        $arrClasses = explode(' ', $arrCssID[1]);
        $arrClasses[] = $objCC->getTable();
        $arrCssID[1] = implode(' ', array_filter(array_unique($arrClasses), 'strlen'));

        $this->cssID = $arrCssID;

        // back link
        $this->Template->referer = '{{env::referer}}';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];


        // class variables for inheritage
        $this->CustomCatalog = $objCC;

        /** @var Hack Marko Cupic 21.10.2018 * */
        if ($blnChangedPublishState)
        {
            $objModel->publish = '';
            $objModel->save();
        }
        /** End Hack */

    }


}