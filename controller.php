<?php

namespace Concrete\Package\MdFormResponderNotification;

use Concrete\Core\Express\Controller\Manager;
use Concrete\Core\Package\Package;
use Macareux\Package\FormResponderNotification\Express\Controller\AutoResponseController;

class Controller extends Package
{
    protected $pkgHandle = 'md_form_responder_notification';
    protected $appVersionRequired = '8.5.5';
    protected $pkgVersion = '0.0.1';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Macareux\Package\FormResponderNotification',
    ];

    public function getPackageName()
    {
        return t('Macareux Form Responder Notification');
    }

    public function getPackageDescription()
    {
        return t('Send responders a copy of their response.');
    }

    public function on_start()
    {
        $forms = (array) $this->getFileConfig()->get('forms');
        if ($forms) {
            /** @var Manager $manager */
            $manager = $this->app->make(Manager::class);
            foreach ($forms as $handle => $form) {
                $manager->extend($handle, function ($app) {
                    return new AutoResponseController($app);
                });
            }
        }
    }
}