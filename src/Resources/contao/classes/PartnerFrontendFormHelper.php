<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 16.10.2018
 * Time: 08:34
 */

namespace Markocupic\FrankfurterPartnerBundle\Contao\Classes;

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
use Contao\FrontendTemplate;
use Contao\MemberGroupModel;
use Contao\Message;
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


    public function __construct($objUser, $objModule)
    {
        $this->objUser = $objUser;
        $this->objModule = $objModule;
    }


    /**
     * Display a wildcard in the back end
     *
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
                        $objPartial->uuid = StringUtil::binToUuid($uuid);
                        $objPartial->fileId = $objFile->id;
                        $strItems .= $objPartial->parse();
                    }
                }
            }
        }
        $objTemplate->items = $strItems;

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

}