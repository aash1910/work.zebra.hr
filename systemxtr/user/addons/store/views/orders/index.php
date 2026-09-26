<div class="container-fluid panel">

<div class="row pagehead">
    <div class="col-xs-6">
        <h2>
            <!-- <i class="fa fa-list-ul"></i> -->
            <?=lang('store.orders')?>
        </h2>
    </div>
    <div class="col-xs-6" style="text-align:right">
        <div class="filters filter-globalsearch">
            <input name="search" type="text" placeholder="<?=lang('store.search')?>">
        </div>
    </div>
</div>

<div class="osections-toggler row">
<ul>
    <?php foreach ($statusList as $name => $label):?>
    <li <?php if ($currentStatus == $name) {
    echo 'class="selected"';
}?> >
        <a href="<?=store_cp_url('orders', null, array('status' => $name, 'paid' => $currentPaid))?>"><?=$label?></a>
    </li>
    <?php endforeach;?>
</ul>
<ul>
    <?php foreach ($paidList as $name => $label):?>
    <li <?php if ($currentPaid == $name) {
    echo 'class="selected"';
}?> >
        <a href="<?=store_cp_url('orders', null, array('paid' => $name, 'status' => $currentStatus))?>"><?=$label?></a>
    </li>
    <?php endforeach;?>
</ul>
</div>

<br>

<?= form_open($post_url, array('id' => 'store_datatable')) ?>

<div class="datatable">
    <table>
        <script type="text/x-subs" class="dtconfig"><?php echo $dtconfig; ?></script>
    </table>
</div>

<div class="tableSubmit" style="padding:20px 0; text-align: right;">
    <?= lang('store.with_selected') ?>
    <?= form_dropdown('with_selected', $with_selected_options) ?>
    <?= form_submit(array('name' => 'update', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')) ?>
</div>

<?= form_close() ?>


</div>