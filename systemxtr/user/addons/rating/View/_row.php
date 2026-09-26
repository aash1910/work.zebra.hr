<?php if ($col_type == 'manage' and empty($disabled)) : ?>
    <div class="toolbar-wrap">
        <?php if (version_compare(APP_VER, '6.0', '<')) : ?>
        <ul class="toolbar">
            <li class="edit"><a  href="<?=$link?>" title="<?=lang('edit')?>"></a></li>
            <?php if (! empty($show_approve_link)) :
                ?><li class="approve"><a href="<?=$approve_link?>" title="<?=lang('unquarantine')?>"></a></li><?php
            endif; ?>
        </ul>
        <?php else : ?>
            <div class="button-toolbar toolbar">
                <div class="button-group button-group-xsmall">
                    <a class="edit button button--default" href="<?=$link?>" title="<?=lang('edit')?>"><span class="hidden"><?=lang('edit')?></span></a>
                    <?php if (! empty($show_approve_link)) :
                        ?><a class="approve button button--default" href="<?=$approve_link?>" title="<?=lang('unquarantine')?>"><span class="hidden"><?=lang('unquarantine')?></span></a><?php
                    endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($col_type == 'flagged') {
    ?>
    <span class="flagged">&nbsp;</span>
    <?php
} ?>

<?php if ($col_type == 'quarantined') {
    ?>
    <span class="quarantined">&nbsp;</span>
    <?php
} ?>
