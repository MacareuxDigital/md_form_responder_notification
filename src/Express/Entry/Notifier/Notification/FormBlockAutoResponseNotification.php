<?php

namespace Macareux\Package\FormResponderNotification\Express\Entry\Notifier\Notification;

use Concrete\Core\Attribute\AttributeValueInterface;
use Concrete\Core\Entity\Attribute\Key\Key;
use Concrete\Core\Entity\Express\Control\Control;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Express\Entry\Notifier\Notification\AbstractFormBlockSubmissionNotification;
use Concrete\Core\Mail\Service;
use Concrete\Core\Package\PackageService;
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

        $toEmail = $service->getToEmail($this->blockController);
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
