<div id="visitor-install" class="panel box">
    <?php echo form_open(ee('CP/URL', $baseUri . 'install')); ?>

    <div class="panel-body tbl-ctrls">
        <h1 class="panel-heading"><?php echo lang('v:installation'); ?></h1>

        <div class="app-notice-wrap"><?php echo ee('CP/Alert')->get('shared-form'); ?></div>

        <table cellspacing="0" style="width:100%">
            <thead>
                <tr>
                    <th style="width:50px">Step</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
<?php
$count = 0;
$num_success = 0;
foreach ($steps as $stepKey => $step) {
    $count++;
    ?>
                <tr>
                    <td><?php echo $count; ?></td>
                    <td><?php echo lang('v:' . $stepKey); ?></td>
                    <td>
                        <?php if ($step['status'] == 'success') { ?>
                            <span class="st-open">Done</span>
                            <?php $num_success++;
                        } else {?>
                            <span class="st-pending">Pending</span>
                        <?php } ?>
                    </td>
                    <td><?php echo $step['msg']; ?></td>
                </tr>
    <?php
}
?>
            </tbody>
        </table>

        <?php if ($num_success != 8) {?>
        <div class="form-btns">
            <input class="btn" type="submit" value="Start Install">
        </div>
        <?php } ?>
    </div>
    <?php form_close();?>
</div>
