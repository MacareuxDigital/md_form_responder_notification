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
use Psr\Log\LoggerInterface;

class FormBlockAutoResponseNotification extends AbstractFormBlockSubmissionNotification
{
    public function notify(Entry $entry, $type)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->app->make(LoggerInterface::class);

        $toEmail = $this->getToEmail($entry);
        $fromEmail = $this->getFromEmail($entry);
        $replyToEmail = $this->getReplyToEmail($entry);
        $template = $this->getTemplate($entry);

        if ($toEmail && $fromEmail && $replyToEmail && $template) {
            /** @var Service $mh */
            $mh = $this->app->make('mail');
            $mh->to($toEmail);
            $mh->from($fromEmail);
            $mh->replyto($replyToEmail);
            $mh->addParameter('entity', $entry->getEntity());
            $mh->addParameter('formName', $this->getFormName($entry));
            $mh->addParameter('attributes', $this->getAttributeValues($entry));
            foreach ($this->getAttributeValues($entry) as $value) {
                /** @var Key $key */
                $key = $value->getAttributeKey();
                $mh->addParameter($key->getAttributeKeyHandle(), $value->getPlainTextValue());
            }
            $mh->load($template);
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
        }
    }

    protected function getToEmail(Entry $entry): string
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->app->make(EntityManagerInterface::class);
        $replyToEmailControlID = $this->blockController->replyToEmailControlID;
        if ($replyToEmailControlID) {
            $control = $entityManager->getRepository(Control::class)->findOneById($replyToEmailControlID);
            if (is_object($control)) {
                foreach ($this->getAttributeValues($entry) as $attribute) {
                    if ($attribute->getAttributeKey()->getAttributeKeyID() == $control->getAttributeKey()->getAttributeKeyID()) {
                        return $attribute->getValue();
                    }
                }
            }
        } else {
            foreach ($this->getAttributeValues($entry) as $attribute) {
                if ($attribute->getAttributeTypeObject()->getAttributeTypeHandle() === 'email') {
                    return $attribute->getValue();
                }
            }
        }

        return '';
    }

    /**
     * @param Entry $entry
     * @return AttributeValueInterface[]
     */
    protected function getAttributeValues(Entry $entry): array
    {
        return $entry->getEntity()->getAttributeKeyCategory()->getAttributeValues($entry);
    }

    protected function getConfig(Entry $entry, string $key): string
    {
        /** @var PackageService $service */
        $service = $this->app->make(PackageService::class);
        $package = $service->getClass('md_form_responder_notification');
        $config = $package->getFileConfig();

        return (string)$config->get('forms.' . $entry->getEntity()->getHandle() . '.' . $key, '');
    }

    protected function getFromEmail(Entry $entry): string
    {
        $email = $this->getConfig($entry, 'from');
        if (empty($email)) {
            $email = (string) $this->app->make('config')->get('concrete.email.form_block.address');
        }

        return $email;
    }

    protected function getReplyToEmail(Entry $entry): string
    {
        $email = $this->getConfig($entry, 'reply_to');
        if (empty($email)) {
            $email = (string) $this->app->make('config')->get('concrete.email.form_block.address');
        }

        return $email;
    }

    protected function getTemplate(Entry $entry): string
    {
        return $this->getConfig($entry, 'template');
    }

    protected function getFormName(Entry $entry): string
    {
        return $entry->getEntity()->getEntityDisplayName();
    }
}
