<?php
defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Support\Facade\Url as UrlFacade;

/** @var \Concrete\Core\View\View $view */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\Form\Service\Form $form */

$entityID = $entityID ?? null;
$from = $from ?? '';
$replyTo = $replyTo ?? '';
$templateType = $templateType ?? 'file';
$templateFile = $templateFile ?? '';
$templateSubject = $templateSubject ?? '';
$templateHtml = $templateHtml ?? '';
$templateBody = $templateBody ?? '';
/** @var \Concrete\Core\Attribute\AttributeKeyInterface[] $keys */
$keys = $keys ?? [];
?>
<form method="post" action="<?= $view->action('save', $entityID) ?>">
    <?php $token->output('save_form_response_settings') ?>
    <fieldset>
        <legend><?= t('Email Settings') ?></legend>
        <div class="form-group">
            <?= $form->label('from', t('From Email')) ?>
            <?= $form->email('from', $from) ?>
        </div>
        <div class="form-group">
            <?= $form->label('replyTo', t('Reply-To Email')) ?>
            <?= $form->email('replyTo', $replyTo) ?>
        </div>
    </fieldset>
    <fieldset>
        <legend><?= t('Email Template') ?></legend>
        <div class="form-group">
            <?= $form->label('templateType', t('Template Type')) ?>
            <div class="form-check">
                <label>
                    <?= $form->radio('templateType', 'manual', $templateType === 'manual') ?>
                    <?= t('Input Manually') ?>
                </label>
            </div>
            <div class="form-check">
                <label>
                    <?= $form->radio('templateType', 'file', $templateType === 'file') ?>
                    <?= t('Template PHP File') ?>
                </label>
            </div>
        </div>
        <div class="form-group ccm-template-type-manual" <?= $templateType === 'file' ? 'style="display: none"' : '' ?>>
            <?= $form->label('templateSubject', t('Subject')) ?>
            <?= $form->text('templateSubject', $templateSubject) ?>
            <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-subject" type="button" tabindex="0"
                    style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                    data-bs-toggle="tooltip" title="<?= t('Form Name') ?>">
                %form_name%
            </button>
            <?php foreach ($keys as $key) { ?>
                <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-subject" type="button" tabindex="0"
                        style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                        data-bs-toggle="tooltip" title="<?= h($key->getAttributeKeyName()) ?>">
                    %<?= $key->getAttributeKeyHandle() ?>%
                </button>
            <?php } ?>
        </div>
        <div class="form-group ccm-template-type-manual" <?= $templateType === 'file' ? 'style="display: none"' : '' ?>>
            <?= $form->label('templateHtml', t('HTML')) ?>
            <?php
            /** @var \Concrete\Core\Editor\EditorInterface $editor */
            $editor = app('editor');
            $editor->getPluginManager()->deselect('concretestyles');
            echo $editor->outputStandardEditor('templateHtml', h($templateHtml));
            ?>
            <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-html" type="button" tabindex="0"
                    style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                    data-bs-toggle="tooltip" title="<?= t('Form Name') ?>">
                %form_name%
            </button>
            <?php foreach ($keys as $key) { ?>
                <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-html" type="button" tabindex="0"
                        style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                        data-bs-toggle="tooltip" title="<?= h($key->getAttributeKeyName()) ?>">
                    %<?= $key->getAttributeKeyHandle() ?>%
                </button>
            <?php } ?>
        </div>
        <div class="form-group ccm-template-type-manual" <?= $templateType === 'file' ? 'style="display: none"' : '' ?>>
            <?= $form->label('templateBody', t('Plain Text')) ?>
            <?= $form->textarea('templateBody', $templateBody, ['rows' => 10]) ?>
            <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-body" type="button" tabindex="0"
                    style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                    data-bs-toggle="tooltip" title="<?= t('Form Name') ?>">
                %form_name%
            </button>
            <?php foreach ($keys as $key) { ?>
                <button class="btn btn-outline-secondary mt-1 ccm-insert-token-to-body" type="button" tabindex="0"
                        style="--bs-btn-font-size: .75rem; --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;"
                        data-bs-toggle="tooltip" title="<?= h($key->getAttributeKeyName()) ?>">
                    %<?= $key->getAttributeKeyHandle() ?>%
                </button>
            <?php } ?>
        </div>
        <div class="form-group ccm-template-type-file" <?= $templateType === 'manual' ? 'style="display: none"' : '' ?>>
            <?= $form->label('templateFile', t('Template PHP File')) ?>
            <?= $form->text('templateFile', $templateFile) ?>
        </div>
    </fieldset>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/mail/form_response') ?>" class="btn btn-secondary"><?= t('Cancel') ?></a>
            <button class="btn btn-primary float-end" type="submit"><?= t('Save') ?></button>
        </div>
    </div>
</form>
<script>
    $(document).ready(function() {
        $('input[name=templateType]').on('change', function() {
            var val = $(this).val();
            $('.ccm-template-type-manual').toggle(val === 'manual');
            $('.ccm-template-type-file').toggle(val === 'file');
        });
        $('.ccm-insert-token-to-subject').on('click', function() {
            const subject = document.getElementById('templateSubject');
            const cursorPos = subject.selectionStart;
            const v = subject.value;
            subject.value = v.substring(0, cursorPos) + $(this).text().trim() + v.substring(cursorPos, v.length);
        });
        $('.ccm-insert-token-to-html').on('click', function() {
            // Get textarea name = templateHtml
            const textarea = document.querySelector('textarea[name=templateHtml]');
            CKEDITOR.instances[textarea.id].insertText($(this).text().trim());
        });
        $('.ccm-insert-token-to-body').on('click', function() {
            const body = document.getElementById('templateBody');
            const cursorPos = body.selectionStart;
            const v = body.value;
            body.value = v.substring(0, cursorPos) + $(this).text().trim() + v.substring(cursorPos, v.length);
        });
    });
</script>