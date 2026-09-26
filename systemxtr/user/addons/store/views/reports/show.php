<div class="container-fluid container-paddingtb panel">
    <div class="row pagehead">
        <div class="col-xs-12">
            <h2><?=lang('store.report')?></h2>
        </div>
    </div>
    <?= form_open($post_url) ?>
        <fieldset class="store_table_fields">
            <!-- <div> -->
                <?php foreach ($report->options()->all() as $key => $value): ?>
                    <div class="store_datatable_field">
                        <?= lang("store.reports.$key", $key) ?>
                        <?= $report->options()->input($key) ?>
                    </div>
                <?php endforeach ?>
                <div class="store_datatable_field">
                    <input type="submit" class="submit button button--primary" value="Update" />
                </div>
            <!-- </div> -->
            <div class="store_datatable_field store_datatable_field_actions_btn">
                <a href="<?= $export_url.(strpos($export_url, '?') === false ? '?' : '&').'print=1' ?>" class="submit button button--primary"><?= lang('store.print') ?></a>
                <a href="<?= $export_url.(strpos($export_url, '?') === false ? '?' : '&').'csv=1' ?>" class="submit button button--primary"><?= lang('store.export_csv') ?></a>
            </div>
        </fieldset>
    <?= form_close() ?>

    <div class="">

        <div class="store_report_html">
        <?php $report->run(); ?>
        </div>

        <?php if (ee()->store->reports->timezone() !== ee()->session->userdata('timezone')): ?>
            <p class="store_report_note"><?= sprintf(lang('store.reports.timezone_note'), '<strong>'.str_replace('_', ' ', ee()->store->reports->timezone()).'</strong>') ?></p>
        <?php endif ?>

    </div>
</div>
