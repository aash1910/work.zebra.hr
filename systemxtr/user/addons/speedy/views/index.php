<?= ee('CP/Alert')->get('shared-form') ?>
<?= ee('CP/Alert')->get('alert-static-files') ?>
<?= ee('CP/Alert')->get('alert-frontedit-check') ?>
<?= ee('CP/Alert')->get('alert-msm') ?>
<?= ee('CP/Alert')->get('alert-queue') ?>
<div class="panel">
    <div class="panel-content" style="padding: 20px">
        <div class="col-group speedy-flex">
            <?php if ($purger->isEnabled()): ?>
                <div class="speedy-column">
                    <div class="speedy-column--content">
                        <h4><?php echo lang('speedy_purge_all') ?></h4>
                        <p class="text-secondary"><?php echo sprintf(lang('speedy_purge_all_desc'), $purger->getName()) ?></p>
                        <div class="toolbar-wrap">
                            <?php echo form_open($purgeAll['base_url'], '') ?>
                            <input type="hidden" name="speedyAction" value="flushAll" />
                            <p class="form-btns"><input type="submit" value="<?= lang('speedy_purge_all_btn') ?>" class="button button--danger" data-work-text="<?= lang('speedy_purge_all_btn_working') ?>"></p>
                            <?php echo form_close() ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="speedy-column">
                <div class="speedy-column--content">
                    <h4><?php echo lang('speedy_flush_all') ?></h4>
                    <p class="text-secondary"><?php echo lang('speedy_flush_all_desc') ?></p>
                    <div class="toolbar-wrap">
                        <?php echo form_open($flushAll['base_url'], '') ?>
                        <input type="hidden" name="speedyAction" value="flushAll" />
                        <p class="form-btns"><input type="submit" value="<?= lang('speedy_flush_all_btn') ?>" class="button button--secondary" data-work-text="<?= lang('speedy_flush_all_btn_working') ?>"></p>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
            <div class="speedy-column">
                <div class="speedy-column--content">
                    <h4><?php echo lang('speedy_flush_driver') ?></h4>
                    <p class="text-secondary"><?php echo lang('speedy_flush_driver_desc') ?></p>
                    <div class="toolbar-wrap">
                        <?php echo form_open($flushDriver['base_url'], '') ?>
                            <p><?php $this->embed('ee:_shared/form/fields/dropdown', [
                                'choices' => $flushDriver['choices'],
                                'field_name' => 'driver_name',
                                'value' => '',
                                ]); ?>
                            </p>
                            <p class="form-btns">
                                <input type="submit" value="<?= lang('speedy_flush_driver_btn') ?>" class="button button--secondary" data-work-text="<?= lang('speedy_flush_driver_btn_working') ?>" data-submit-text="<?= lang('speedy_flush_driver_btn') ?>" />
                            </p>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
            <div class="speedy-column">
                <div class="speedy-column--content">
                    <h4><?php echo lang('speedy_refresh_data') ?></h4>
                    <p class="text-secondary"><?php echo lang('speedy_refresh_data_desc') ?></p>
                    <div class="toolbar-wrap">
                        <?php echo form_open($refreshAll['base_url'], '') ?>
                        <input type="hidden" name="speedyAction" value="refresh" />
                        <p class="form-btns"><input type="submit" value="<?= lang('speedy_refresh_data_btn') ?>" class="button button--secondary" data-work-text="<?= lang('speedy_refresh_data_btn_working') ?>"></p>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
            <div class="speedy-column">
                <div class="speedy-column--content">
                    <h4><?php echo lang('speedy_tag_clearing') ?></h4>
                    <div class="toolbar-wrap">
                        <p class="form-btns">
                            <a class="button button--secondary" href="<?php echo $clearTags['base_url'] ?>">Choose Tags</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3><?= lang('speedy_settings') ?></h3>
        </div>
    </div>
    <div class="table-responsive table-responsive--collapsible">
        <?php $this->embed('ee:_shared/table', $cacheData) ?>
    </div>
</div>

<?php if ($diagnosticsData): ?>

    <div class="panel">
        <div class="panel-heading">
            <div class="title-bar">
                <h3><?= lang('speedy_diagnostics_overview') ?></h3>
            </div>
            <div>
                <p style="color: var(--ee-text-secondary)"><?= lang('speedy_diagnostics_desc') ?></p>
            </div>
        </div>
        <div class="table-responsive table-responsive--collapsible">
            <?php $this->embed('ee:_shared/table', $diagnosticsData) ?>
        </div>
        <?php if ($diagnosticsData['total_rows'] > $diagnosticsData['limit']): ?>
        <div class="footer">
            View all
        </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?= ee('CP/Alert')->get('alert-clear-cache-url') ?>
