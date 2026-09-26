<table class="mainTable">
    <thead>
        <tr><th colspan="2"><?php echo lang('ci:watermark:settings')?></th></tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo lang('ci:watermark:type')?></td>
            <td>
                <?php if (isset($type) == false) {
    $type = 'none';
}?>
                <input type="radio" value="none" class="ci_watermark_type" name="ci_watermark[type]" <?php if ($type == 'none') {
    echo 'checked';
}?> /> &nbsp;<?php echo lang('ci:watermark:type:none')?> &nbsp;&nbsp;&nbsp;
                <input type="radio" value="text" class="ci_watermark_type" name="ci_watermark[type]" <?php if ($type == 'text') {
    echo 'checked';
}?> /> &nbsp;<?php echo lang('ci:watermark:type:text')?> &nbsp;&nbsp;&nbsp;
                <input type="radio" value="image" class="ci_watermark_type" name="ci_watermark[type]" <?php if ($type == 'image') {
    echo 'checked';
}?> /> &nbsp;<?php echo lang('ci:watermark:type:image')?>
            </td>
        </tr>
        <tr class="ci_watermark_general">
            <td><?php echo lang('ci:watermark:padding')?></td>
            <td>
                <input type="text" name="ci_watermark[padding]" value="<?php if (isset($padding) == false) {
    echo 0;
} else {
    echo $padding;
}?>"  /> <br />
                <?php echo lang('ci:watermark:padding:exp')?>
            </td>
        </tr>
        <tr class="ci_watermark_general">
            <td><?php echo lang('ci:watermark:horalign')?></td>
            <td>
                <?php $temp = array('left' => lang('ci:watermark:left'), 'center' => lang('ci:watermark:center'), 'right' => lang('ci:watermark:right'));?>
                <?php echo form_dropdown('ci_watermark[horizontal_alignment]', $temp, ((isset($horizontal_alignment) == false) ? 'center' : $horizontal_alignment));?>
                <?php echo lang('ci:watermark:horalign:exp')?>
            </td>
        </tr>
        <tr class="ci_watermark_general">
            <td><?php echo lang('ci:watermark:vrtalign')?></td>
            <td>
                <?php $temp = array('top' => lang('ci:watermark:top'), 'middle' => lang('ci:watermark:middle'), 'bottom' => lang('ci:watermark:bottom'));?>
                <?php echo form_dropdown('ci_watermark[vertical_alignment]', $temp, ((isset($vertical_alignment) == false) ? 'bottom' : $vertical_alignment));?>
                <?php echo lang('ci:watermark:vrtalign:exp')?>
            </td>
        </tr>
        <tr class="ci_watermark_general">
            <td><?php echo lang('ci:watermark:horoffset')?></td>
            <td>
                <input type="text" name="ci_watermark[horizontal_offset]" value="<?php if (isset($horizontal_offset) == false) {
    echo 0;
} else {
    echo $horizontal_offset;
}?>"  /> <br />
                <?php echo lang('ci:watermark:horoffset:exp')?>
            </td>
        </tr>
        <tr class="ci_watermark_general">
            <td><?php echo lang('ci:watermark:vrtoffset')?></td>
            <td>
                <input type="text" name="ci_watermark[vertical_offset]" value="<?php if (isset($vertical_offset) == false) {
    echo 0;
} else {
    echo $vertical_offset;
}?>"  /> <br />
                <?php echo lang('ci:watermark:vrtoffset:exp')?>
            </td>
        </tr>
    </tbody>
</table>


<table class="mainTable ci_watermark_text">
    <thead>
        <tr><th colspan="2"><?php echo lang('ci:watermark:text_pref')?></th></tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo lang('ci:watermark:text')?></td>
            <td>
                <input type="text" name="ci_watermark[text]" value="<?php if (isset($text) == false) {
    echo 'EEHarbor.Com (Channel Images)';
} else {
    echo $text;
}?>"  /> <br />
                <?php echo lang('ci:watermark:text:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:font_path')?></td>
            <td>
                <input type="text" name="ci_watermark[font_path]" value="<?php if (isset($font_path) == false) {
    echo '';
} else {
    echo $font_path;
}?>"  /> <br />
                <?php echo lang('ci:watermark:font_path:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:font_size')?></td>
            <td>
                <input type="text" name="ci_watermark[font_size]" value="<?php if (isset($font_size) == false) {
    echo '16';
} else {
    echo $font_size;
}?>"  /> <br />
                <?php echo lang('ci:watermark:font_size:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:font_color')?></td>
            <td>
                <input type="text" name="ci_watermark[font_color]" value="<?php if (isset($font_color) == false) {
    echo 'ffffff';
} else {
    echo $font_color;
}?>"  /> <br />
                <?php echo lang('ci:watermark:font_color:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:shadow_color')?></td>
            <td>
                <input type="text" name="ci_watermark[shadow_color]" value="<?php if (isset($shadow_color) == false) {
    echo '';
} else {
    echo $shadow_color;
}?>"  /> <br />
                <?php echo lang('ci:watermark:shadow_color:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:shadow_distance')?></td>
            <td>
                <input type="text" name="ci_watermark[shadow_distance]" value="<?php if (isset($shadow_distance) == false) {
    echo 3;
} else {
    echo $shadow_distance;
}?>"  /> <br />
                <?php echo lang('ci:watermark:shadow_distance:exp')?>
            </td>
        </tr>
    </tbody>
</table>


<table class="mainTable ci_watermark_image">
    <thead>
        <tr><th colspan="2"><?php echo lang('ci:watermark:overlay_pref')?></th></tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo lang('ci:watermark:overlay_path')?></td>
            <td>
                <input type="text" name="ci_watermark[overlay_path]" value="<?php if (isset($overlay_path) == false) {
    echo '';
} else {
    echo $overlay_path;
}?>"  /> <br />
                <?php echo lang('ci:watermark:overlay_path:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:opacity')?></td>
            <td>
                <input type="text" name="ci_watermark[opacity]" value="<?php if (isset($opacity) == false) {
    echo 50;
} else {
    echo $opacity;
}?>"  /> <br />
                <?php echo lang('ci:watermark:opacity:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:x_trans')?></td>
            <td>
                <input type="text" name="ci_watermark[x_transp]" value="<?php if (isset($x_transp) == false) {
    echo 4;
} else {
    echo $x_transp;
}?>"  /> <br />
                <?php echo lang('ci:watermark:x_trans:exp')?>
            </td>
        </tr>
        <tr>
            <td><?php echo lang('ci:watermark:y_trans')?></td>
            <td>
                <input type="text" name="ci_watermark[y_transp]" value="<?php if (isset($y_transp) == false) {
    echo 4;
} else {
    echo $y_transp;
}?>"  /> <br />
                <?php echo lang('ci:watermark:y_trans:exp')?>
            </td>
        </tr>
    </tbody>
</table>

<a href="#" class="CITestWaterMark ci_watermark_general"><?php echo lang('ci:watermark:test_wm')?></a>