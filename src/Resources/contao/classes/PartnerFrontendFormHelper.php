<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */

namespace Markocupic\FrankfurterPartnerBundle\Contao\Classes;

use Contao\Controller;
use Contao\Database;
use Contao\FilesModel;
use Contao\File;
use Contao\FrontendTemplate;
use Contao\Message;
use Contao\StringUtil;
use Contao\Validator;
use Contao\System;
use Contao\Config;


/**
 * Class PartnerFrontendFormHelper
 * @package Markocupic\FrankfurterPartnerBundle\Contao\Classes
 */
class PartnerFrontendFormHelper
{

    /**
     * @var
     */
    protected $objUser;

    /**
     * @var
     */
    protected $objModule;

    /**
     * PartnerFrontendFormHelper constructor.
     * @param $objUser
     * @param $objModule
     */
    public function __construct($objUser, $objModule)
    {
        $this->objUser = $objUser;
        $this->objModule = $objModule;
    }


    /**
     * @return string
     */
    public function generateGallery()
    {
        $objTemplate = new FrontendTemplate('modPartnerFrontendGalleryPartial');

        $strItems = '';
        $arrImages = StringUtil::deserialize($this->objModule->gallery, true);
        foreach ($arrImages as $uuid)
        {

            if (Validator::isBinaryUuid($uuid))
            {
                $objFile = FilesModel::findByUuid($uuid);
                if ($objFile !== null)
                {
                    if (is_file(TL_ROOT . '/' . $objFile->path))
                    {
                        $objPartial = new FrontendTemplate('partnerFrontendGalleryPartial');
                        $objPartial->class = 'partner-gallery-item';
                        $objPartial->uuid = StringUtil::binToUuid($uuid);
                        $objPartial->fileId = $objFile->id;
                        $objPartial->hasImage = true;
                        $strItems .= $objPartial->parse();
                    }
                }
            }
        }
        $objTemplate->items = $strItems;

        return Controller::replaceInsertTags($objTemplate->parse());

    }

    /**
     * @param $fieldname
     * @return string
     */
    public function generateProductImage($fieldname)
    {
        $objTemplate = new FrontendTemplate('modPartnerFrontendProductImagePartial');
        $objTemplate->class = 'partner-logo-image';
        return $this->generateImage($objTemplate, $fieldname);
    }

    /**
     * @param $fieldname
     * @return string
     */
    public function generateLogoImage($fieldname)
    {
        $objTemplate = new FrontendTemplate('modPartnerFrontendLogoImagePartial');
        $objTemplate->class = 'partner-logo-image';
        return $this->generateImage($objTemplate, $fieldname);
    }

    /**
     * @param $fieldname
     * @return string
     */
    public function generateBrandImage($fieldname)
    {
        $objTemplate = new FrontendTemplate('modPartnerFrontendBrandImagePartial');
        $objTemplate->class = 'partner-brand-image';
        return $this->generateImage($objTemplate, $fieldname);
    }

    /**
     * @param $fieldname
     * @return string
     */
    public function generateMainImage($fieldname)
    {
        $objTemplate = new FrontendTemplate('modPartnerFrontendMainImagePartial');
        $objTemplate->class = 'partner-main-image';
        return $this->generateImage($objTemplate, $fieldname);
    }

    /**
     * @param $objTemplate
     * @param $fieldname
     * @return string
     */
    protected function generateImage($objTemplate, $fieldname)
    {
        $uuid = $this->objModule->{$fieldname};
        if (Validator::isBinaryUuid($uuid))
        {
            $objFile = FilesModel::findByUuid($uuid);
            if ($objFile !== null)
            {
                if (is_file(TL_ROOT . '/' . $objFile->path))
                {
                    $objTemplate->uuid = StringUtil::binToUuid($uuid);
                    $objTemplate->fileId = $objFile->id;
                    $objTemplate->fieldname = $fieldname;
                    $objTemplate->hasImage = true;
                }
            }
        }

        return Controller::replaceInsertTags($objTemplate->parse());
    }


    /**
     * Rotate an image clockwise by 90°
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function rotateImage($id)
    {
        $angle = 90;

        $objFiles = FilesModel::findById($id);
        if ($objFiles === null)
        {
            return false;
        }

        $src = $objFiles->path;

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!file_exists($rootDir . '/' . $src))
        {
            Message::addError(sprintf('File "%s" not found.', $src));
            return false;
        }

        $objFile = new File($src);
        if (!$objFile->isGdImage)
        {
            Message::addError(sprintf('File "%s" could not be rotated because it is not an image.', $src));
            return false;
        }

        if (!function_exists('imagerotate'))
        {
            Message::addError(sprintf('PHP function "%s" is not installed.', 'imagerotate'));
            return false;
        }

        $source = imagecreatefromjpeg($rootDir . '/' . $src);

        //rotate
        $imgTmp = imagerotate($source, $angle, 0);

        // Output
        imagejpeg($imgTmp, $rootDir . '/' . $src);

        imagedestroy($source);
        
        return true;
    }

    /**
     * @return array
     */
    public function getCatTags()
    {
        $opt = array();
        $pids = Config::get('partnerCatPid');
        if ($pids != '')
        {
            if (is_array($pids))
            {
                $objDb = Database::getInstance()->execute("SELECT * FROM tl_pct_customelement_tags WHERE pid IN (" . implode(',', $pids) . ") ORDER BY sorting");
            }
            else
            {
                $objDb = Database::getInstance()->prepare('SELECT * FROM tl_pct_customelement_tags WHERE pid=? ORDER BY sorting')->execute($pids);
            }

            while ($objDb->next())
            {
                $opt[$objDb->id] = $objDb->title;
            }
        }

        return $opt;
    }
}