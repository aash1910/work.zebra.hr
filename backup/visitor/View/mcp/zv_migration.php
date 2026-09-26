<div id="visitor-install" class="box">
    <?php echo form_open(ee('CP/URL', $baseUri . 'migrate_post')); ?>

    <div class="tbl-ctrls">
        <h1><?php echo lang('v:zv_migration'); ?></h1>
        <p><?php echo lang('v:zv_migration_desc'); ?></p>

        <div class="app-notice-wrap"><?php echo ee('CP/Alert')->get('shared-form'); ?></div>

<?php
$count = 0;
$lastStepType = '';
foreach ($steps as $stepKey => $step) {
    $count++;

    if ($step['type'] !== $lastStepType) {
        $lastStepType = $step['type'];
        echo '<h2 class="step-type">', ucfirst($step['type']), ' Steps</h2>';
        echo '<div class="step-type-desc">', lang('v:zv_step_' . $step['type'] . '_desc'), '</div>';
    }
    ?>
        <fieldset class="step <?php echo $step['type']; ?>">
            <div class="field-instruct">
                <span class="status st-<?php echo ($step['status'] === 'success' ? 'open' : 'error'); ?>"><?php echo $step['status_label']; ?></span>

                <label><?php echo lang('v:' . $stepKey); ?></label>
                <em><?php echo $step['msg']; ?></em>
            </div>
        </fieldset>
    <?php
}
?>
        <div class="form-btns">
            <input class="btn" <?php if ($readyToMigrate !== true) {
                echo 'disabled';
                               } ?> type="submit" value="Migrate">
        </div>
    </div>
    <?php form_close();?>
</div>