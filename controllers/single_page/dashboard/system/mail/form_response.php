<?php

namespace Concrete\Package\MdFormResponderNotification\Controller\SinglePage\Dashboard\System\Mail;

use Concrete\Block\ExpressForm\Controller;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\Tree\Node\Type\ExpressEntryCategory;
use Concrete\Core\Tree\Type\ExpressEntryResults;
use Concrete\Core\Validator\String\EmailValidator;
use Macareux\Package\FormResponderNotification\Express\Service\ExpressFormService;

class FormResponse extends DashboardPageController
{
    protected function getParentNode($folder = null)
    {
        if ($folder) {
            $node = Node::getByID($folder);
            if (!($node instanceof ExpressEntryCategory) && !($node instanceof ExpressEntryResults)) {
                throw new \Exception(t('Invalid form entry node.'));
            }
        } else {
            $node = ExpressEntryCategory::getNodeByName(Controller::FORM_RESULTS_CATEGORY_NAME);
            if (!($node instanceof ExpressEntryCategory)) {
                throw new \Exception(t('Valid form entry category cannot be found. If you have removed or renamed this element you must reinstate it.'));
            }
        }
        return $node;
    }

    public function view($folder = null)
    {
        $node = $this->getParentNode($folder);
        $node->populateDirectChildrenOnly();
        $this->set('nodes', $node->getChildNodes());
        $this->setThemeViewTemplate('full.php');
    }

    public function form($entityID)
    {
        $r = $this->entityManager->getRepository(Entity::class);
        if ($entityID) {
            $entity = $r->findOneById($entityID);
        }
        /** @var Entity $entity */
        if ($entity) {
            $this->set('entityID', $entityID);

            /** @var ExpressFormService $service */
            $service = $this->app->make(ExpressFormService::class, ['object' => $entity]);
            $this->set('from', $service->getFromEmail());
            $this->set('replyTo', $service->getReplyToEmail());
            $templateFile = $service->getTemplateFile();
            $this->set('templateType', $templateFile ? 'file' : 'manual');
            $this->set('templateFile', $templateFile);
            $this->set('templateSubject', $service->getTemplateSubject(true));
            $this->set('templateHtml', LinkAbstractor::translateFromEditMode($service->getTemplateHtml(true)));
            $this->set('templateBody', $service->getTemplateBody(true));

            $this->set('keys', $service->getAttributeKeys());

            $this->set('pageTitle', t('Form Response Settings for "%s" form', $service->getFormName()));
            $this->render('/dashboard/system/mail/form_response/form', 'md_form_responder_notification');
        } else {
            throw new UserMessageException(t('Invalid express entity ID.'));
        }
    }

    public function save($entityID)
    {
        $r = $this->entityManager->getRepository(Entity::class);
        if ($entityID) {
            $entity = $r->findOneById($entityID);
        }
        /** @var Entity $entity */
        if ($entity) {
            if (!$this->token->validate('save_form_response_settings')) {
                $this->error->add($this->token->getErrorMessage());
            }

            /** @var EmailValidator $emailValidator */
            $emailValidator = $this->app->make(EmailValidator::class);

            $from = $this->request->request->get('from');
            $emailValidator->isValid($from, $this->error);

            $replyTo = $this->request->request->get('replyTo');
            $emailValidator->isValid($replyTo, $this->error);

            $templateType = $this->request->request->get('templateType');
            $templateFile = $this->request->request->get('templateFile');
            $templateSubject = $this->request->request->get('templateSubject');
            $templateHtml = $this->request->request->get('templateHtml');
            $templateBody = $this->request->request->get('templateBody');

            if ($templateType === 'file') {
                if (!$templateFile) {
                    $this->error->add(t('You must specify a template file.'));
                }
                $template = $templateFile;
            } else {
                if (!$templateSubject) {
                    $this->error->add(t('You must specify a subject.'));
                }
                if (!$templateHtml) {
                    $this->error->add(t('You must specify an HTML template.'));
                }
                if (!$templateBody) {
                    $this->error->add(t('You must specify a plain text template.'));
                }
                $template = [
                    'subject' => $templateSubject,
                    'html' => LinkAbstractor::translateTo($templateHtml),
                    'body' => $templateBody
                ];
            }

            if (!$this->error->has()) {
                /** @var ExpressFormService $service */
                $service = $this->app->make(ExpressFormService::class, ['object' => $entity]);
                $service->setConfig('from', $from);
                $service->setConfig('reply_to', $replyTo);
                $service->setConfig('template', $template);

                $this->flash('success', t('Form response settings have been saved.'));

                return $this->buildRedirect('/dashboard/system/mail/form_response');
            } else {
                $this->form($entityID);
            }
        } else {
            throw new UserMessageException(t('Invalid express entity ID.'));
        }
    }
}