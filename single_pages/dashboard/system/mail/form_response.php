<?php

use Concrete\Core\Tree\Node\Type\ExpressEntryResults;

defined('C5_EXECUTE') or die("Access Denied.");
/** @var \Concrete\Core\View\View $view */
/** @var \Concrete\Core\Tree\Node\Node[] $nodes */
$nodes = $nodes ?? [];
?>
<div class="table-responsive">
    <table class="ccm-search-results-table">
        <thead>
        <tr>
            <th></th>
            <th class=""><span><?=t('Name')?></span></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($nodes as $node) {
            $detailsURL = null;
            $formatter = $node->getListFormatter();
                if ($node instanceof ExpressEntryResults) {
                    $entity = $node->getEntity();
                    if ($entity) {
                        $detailsURL = $view->action('form', $node->getEntity()->getID());
                    }
                } else {
                    $detailsURL = $view->action('view', $node->getTreeNodeID());
                }
                ?>
                <tr <?php if ($detailsURL) { ?>data-details-url="<?=$detailsURL?>"<?php } ?>
                    class="<?=$formatter->getSearchResultsClass()?>">
                    <td class="ccm-search-results-icon"><?=$formatter->getIconElement()?></td>
                    <td class="ccm-search-results-name"><?=$node->getTreeNodeDisplayName()?></td>
                </tr>
            <?php
        } ?>
        </tbody>
    </table>
</div>
