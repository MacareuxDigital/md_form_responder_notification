<?php

namespace Macareux\Package\FormResponderNotification\Express\Controller;

use Concrete\Block\ExpressForm\Controller;
use Concrete\Core\Express\Controller\StandardController;
use Concrete\Core\Express\Entry\Notifier\NotificationProviderInterface;
use Macareux\Package\FormResponderNotification\Express\Entry\Notifier\Notification\FormBlockAutoResponseNotification;

class AutoResponseController extends StandardController
{
    public function getNotifier(NotificationProviderInterface $provider = null)
    {
        $notifier = parent::getNotifier($provider);

        if ($provider instanceof Controller) {
            $autoResponseNotification = new FormBlockAutoResponseNotification($this->app, $provider);
            $notifier->getNotificationList()->addNotification($autoResponseNotification);
        }

        return $notifier;
    }
}
