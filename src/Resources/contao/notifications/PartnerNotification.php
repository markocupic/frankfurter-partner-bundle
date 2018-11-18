<?php
/**
 * Partner Bundle Plugin for Contao
 * Copyright (c) 2008-2018 Marko Cupic & Leif Braun from kreadea
 * @package frankfurter-partner-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2018
 * @link https://github.com/markocupic/frankfurter-partner-bundle
 */


namespace Markocupic\FrankfurterPartnerBundle\Contao\Notifications;


use Contao\CcCardealerModel;
use Contao\Config;
use Contao\Database;
use Contao\Environment;
use NotificationCenter\Model\Notification;


/**
 * Class PartnerNotification
 * @package Markocupic\FrankfurterPartnerBundle\Contao\Notifications
 */
class PartnerNotification
{

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function sendNotification()
    {
        $notificationId = Config::get('notification_advice_admin_on_new_entries');
        if ($notificationId > 0)
        {
            $objPartner = Database::getInstance()->prepare('SELECT * FROM cc_cardealer WHERE fetstamp>?')->execute(1);
            while ($objPartner->next())
            {
                $objNotification = Notification::findByPk($notificationId);
                if ($objNotification !== null)
                {
                    // Set token array
                    $arrTokens = array(
                        'partner_name'  => html_entity_decode($objPartner->name),
                        'hostname'      => html_entity_decode(Environment::get('host')),
                        'partner_id'    => $objPartner->id,
                        'partner_alias' => html_entity_decode($objPartner->alias),
                        'preview_token' => html_entity_decode($objPartner->previewtoken),
                        'publish_state' => $objPartner->publish ? html_entity_decode('veröffentlicht') : html_entity_decode('unveröffentlicht')
                    );

                    $objNotification->send($arrTokens, 'de');

                    // Set fetstamp to ''
                    $objCardealerModel = CcCardealerModel::findByPk($objPartner->id);
                    $objCardealerModel->fetstamp = '';
                    $objCardealerModel->save();
                }
            }
        }
    }
}

