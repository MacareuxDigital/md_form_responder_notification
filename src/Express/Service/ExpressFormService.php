<?php

namespace Macareux\Package\FormResponderNotification\Express\Service;

use Concrete\Block\ExpressForm\Controller as ExpressFormBlockController;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Attribute\AttributeKeyInterface;
use Concrete\Core\Attribute\AttributeValueInterface;
use Concrete\Core\Config\Repository\Liaison;
use Concrete\Core\Entity\Express\Control\Control;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\Package\FormResponderNotification\Editor\LinkAbstractor;

class ExpressFormService implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    protected Entity $entity;

    protected ?Entry $entry = null;

    protected Liaison $config;

    /**
     * @param Entity|Entry $object
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct($object)
    {
        if ($object instanceof Entry) {
            $this->entry = $object;
            $this->entity = $object->getEntity();
        } elseif ($object instanceof Entity) {
            $this->entity = $object;
        } else {
            throw new \Exception(t('Invalid object type.'));
        }

        /** @var PackageService $service */
        $app = Application::getFacadeApplication();
        $service = $app->make(PackageService::class);
        $package = $service->getClass('md_form_responder_notification');
        $this->config = $package->getFileConfig();
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getToEmail(ExpressFormBlockController $controller): string
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->app->make(EntityManagerInterface::class);
        $replyToEmailControlID = $controller->replyToEmailControlID;
        if ($replyToEmailControlID) {
            $control = $entityManager->getRepository(Control::class)->findOneById($replyToEmailControlID);
            if (is_object($control)) {
                foreach ($this->getAttributeValues() as $attribute) {
                    if ($attribute->getAttributeKey()->getAttributeKeyID() == $control->getAttributeKey()->getAttributeKeyID()) {
                        return $attribute->getValue();
                    }
                }
            }
        }
        foreach ($this->getAttributeValues() as $attribute) {
            if ($attribute->getAttributeTypeObject()->getAttributeTypeHandle() === 'email') {
                return $attribute->getValue();
            }
        }


        return '';
    }

    /**
     * @return AttributeValueInterface[]
     */
    public function getAttributeValues(): array
    {
        if ($this->getEntry()) {
            return $this->getEntity()->getAttributeKeyCategory()->getAttributeValues($this->getEntry());
        }

        return [];
    }

    public function getEntry(): ?Entry
    {
        return $this->entry;
    }

    /**
     * @return AttributeKeyInterface[]
     */
    public function getAttributeKeys(): array
    {
        return $this->getEntity()->getAttributeKeyCategory()->getList();
    }

    public function getFromEmail(): string
    {
        $email = (string) $this->getConfig('from');
        if (empty($email)) {
            $email = (string) $this->app->make('config')->get('concrete.email.form_block.address');
        }

        return $email;
    }

    public function getConfig(string $key)
    {
        return $this->config->get('forms.' . $this->getEntity()->getHandle() . '.' . $key, '');
    }

    public function setConfig(string $key, $value)
    {
        $this->config->save('forms.' . $this->getEntity()->getHandle() . '.' . $key, $value);
    }

    public function getReplyToEmail(): string
    {
        $email = (string) $this->getConfig('reply_to');
        if (empty($email)) {
            $email = (string) $this->app->make('config')->get('concrete.email.form_block.address');
        }

        return $email;
    }

    public function getTemplateFile(): string
    {
        return is_string($this->getConfig('template')) ? $this->getConfig('template') : '';
    }

    public function getTemplateSubject($raw = false): string
    {
        $subject = (string) $this->config->get('forms.' . $this->getEntity()->getHandle() . '.template.subject', '');

        if (!$raw) {
            $subject = $this->convertToken($subject);
        }

        return $subject;
    }

    public function getTemplateHtml($raw = false): string
    {
        $html = (string) $this->config->get('forms.' . $this->getEntity()->getHandle() . '.template.html', '');

        if (!$raw) {
            $html = $this->convertToken(LinkAbstractor::translateFrom($html));
        }

        return $html;
    }

    public function getTemplateBody($raw = false): string
    {
        $body = (string) $this->config->get('forms.' . $this->getEntity()->getHandle() . '.template.body', '');

        if (!$raw) {
            $body = $this->convertToken($body);
        }

        return $body;
    }

    public function convertToken($text)
    {
        $text = str_replace('%form_name%', $this->getFormName(), $text);

        $attributeValues = $this->getAttributeValues();
        foreach ($attributeValues as $value) {
            $key = $value->getAttributeKey();
            $text = str_replace('%' . $key->getAttributeKeyHandle() . '%', $value->getPlainTextValue(), $text);
        }

        return $text;
    }

    public function getFormName(): string
    {
        return $this->getEntity()->getEntityDisplayName();
    }
}
