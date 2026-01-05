<?php

namespace Macareux\Package\FormResponderNotification\Express\Entry\Notifier\Notification;

use Concrete\Core\Attribute\AttributeValueInterface;
use Concrete\Core\Entity\Attribute\Key\Key;
use Concrete\Core\Entity\Express\Control\Control;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Express\Entry\Notifier\Notification\AbstractFormBlockSubmissionNotification;
use Concrete\Core\Mail\Service;
use Concrete\Core\Package\PackageService;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\Package\FormResponderNotification\Express\Service\ExpressFormService;
use Psr\Log\LoggerInterface;

class FormBlockAutoResponseNotification extends AbstractFormBlockSubmissionNotification
{
    public function notify(Entry $entry, $type)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->app->make(LoggerInterface::class);
        /** @var ExpressFormService $service */
        $service = $this->app->make(ExpressFormService::class, ['object' => $entry]);

        // If the "disable_auto_response" flag is enabled in the form settings, skip sending the auto-response entirely.
        if ($service->getConfig('disable_auto_response')) {
            $logger->debug(sprintf(
                "Auto-response disabled by config for form %s (auto-response skipped)",
                $service->getFormName()
            ));
            // Return early to prevent any further processing or email sending.
            return;
        }

        $user = new User();
        $toEmail = $service->getToEmail($this->blockController);
        $sendToLoggedUser = (bool) $service->getConfig('send_to_logged_user');

        if (!$toEmail && $sendToLoggedUser && $user->isRegistered()) {
            $userInfo = $user->getUserInfoObject();
            $toEmail = $userInfo ? $userInfo->getUserEmail() : null;
        }

        $fromEmail = $service->getFromEmail();
        $replyToEmail = $service->getReplyToEmail() ?: $fromEmail;
        $template = $service->getTemplateFile();
        $subject = $service->getTemplateSubject();
        $html = $service->getTemplateHtml();
        $body = $service->getTemplateBody();

        if ($toEmail && $fromEmail && $replyToEmail && ($template || ($subject && $html && $body))) {
            /** @var Service $mh */
            $mh = $this->app->make('mail');
            $mh->to($toEmail);
            $mh->from($fromEmail);
            $mh->replyto($replyToEmail);
            if ($template) {
                $mh->addParameter('entity', $service->getEntity());
                $mh->addParameter('formName', $service->getFormName());
                $attributeValues = $service->getAttributeValues();
                $mh->addParameter('attributes', $attributeValues);
                foreach ($attributeValues as $value) {
                    $key = $value->getAttributeKey();
                    $mh->addParameter($key->getAttributeKeyHandle(), $value->getPlainTextValue());
                }
                $mh->load($template);
            }
            if ($subject && $html && $body) {
                $mh->setSubject($subject);
                $mh->setBody($body);
                $mh->setBodyHTML($html);
            }
            try {
                $mh->sendMail();
            } catch (\Exception $e) {
                $logger->notice(
                    sprintf("Failed to send auto response.\nResponder: %s\nForm: %s\nMessage: %s (%s:%s)",
                        $toEmail,
                        $entry->getEntity()->getHandle(),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    )
                );
            }
        } else {
            $logger->debug(sprintf("Auto response for %s not sent. Missing email or template.", $entry->getPublicIdentifier()));
        }
    }
}
