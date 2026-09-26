<?php if (isset($options)) {
    ?>
<div class="multi-select">
    <div class="scroll-wrap">
    <?php foreach ($options as $key => $val) {
        $chk = $cho = '';
        if (isset($selected) and in_array($key, $selected)) {
            $chk    = ' checked="checked"';
            $cho    = ' chosen';
        } ?>
        <label class="choice block<?=$cho?>">
            <input type="checkbox" name="<?=$name?>[]" value="<?=$key?>"<?=$chk?> /> <?=$val?>
        </label>
        <?php
    } ?>
    </div>
</div>
    <?php
} ?>