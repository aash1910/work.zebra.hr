<?= ee('CP/Alert')->getAllInlines() ?>

<div class="panel">
    <div class="panel-heading">
        <div class="form-btns form-btns-top">
            <div class="title-bar title-bar--large">
                <h3 class="title-bar__title"><?= $cp_page_title ?></h3>
                <div class="title-bar__extra-tools">
                    <a href="<?= $flush_driver['href'] ?>" class="btn"><?= $flush_driver['content'] ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive table-responsive--collapsible">
        <?php $this->embed('ee:_shared/table', $driver_items); ?>
    </div>
</div>
