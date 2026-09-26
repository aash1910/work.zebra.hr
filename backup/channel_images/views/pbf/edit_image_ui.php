<div class="ci_eiw">

    <div class="eiw_left">
        <ul class="sizes">
            <li class="label"><label><?php echo lang('ci:sizes')?></label></li>
            <li class="current"><a href="#" data-name="ORIGINAL">ORIGINAL</a></li></li>
            <?php foreach($sizes as $size_name => $dimensions):?>
            <li><a href="#" data-name="<?php echo $size_name?>" data-width="<?php echo $dimensions['width']?>" data-height="<?php echo $dimensions['height']?>"><?php echo $size_name?></a></li></li>
            <?php endforeach;?>
        </ul>

        <ul class="actions">
            <li class="label"><label><?php echo lang('ci:actions')?></label></li>
            <li><a href="#" data-action="crop"><?php echo lang('ci:crop')?></a></li></li>
            <li><a href="#" data-action="rotate-left" class="rotate"><?php echo lang('ci:rotate_left')?></a></li>
            <li><a href="#" data-action="rotate-right" class="rotate"><?php echo lang('ci:rotate_right')?></a></li>
            <li><a href="#" data-action="flip-hor"><?php echo lang('ci:flip_hor')?></a></li>
            <li><a href="#" data-action="flip-ver"><?php echo lang('ci:flip_ver')?></a></li>
        </ul>

        <div class="ci_eiw_bottombar bottombar">
            <span class="crop_holder" style="display:none">
                <a class="btn tn action submit button button--secondary apply_crop"><?php echo lang('ci:apply_crop')?></a>
                <a class="btn tn action submit button button--secondary cancel_crop"><?php echo lang('ci:cancel_crop')?></a>
                <br clear="all"><br>
                X:&nbsp;&nbsp;&nbsp;<input type="text" class="jcrop_x">&nbsp;&nbsp;&nbsp;&nbsp;Y: <input type="text" class="jcrop_y"> <br>
                X2: <input type="text" class="jcrop_x2">&nbsp;&nbsp;Y2: <input type="text" class="jcrop_y2">
                <br clear="all"><br>
                <a class="btn tn action submit button button--secondary set_sel"><?php echo lang('ci:set_crop_sel')?></a>
            </span>
            <span class="save_image_holder">
                <span class="regen_sizes"><input type="checkbox" name="regen_sizes" value="yes"> <?php echo lang('ci:regen_sizes')?><br><br></span>
                <a class="btn submit button button--secondary save_image"><?php echo lang('ci:save_img')?></a>
                <a class="btn submit button button--secondary cancel_image"><?php echo lang('ci:cancel')?></a>
            </span>
            <p class="loading">loading....</p>
        </div>

        <br><br>
    </div>

    <div class="imgholder" style="text-align:center;">
        <img src="<?php echo $img_url?>" data-alturl="<?php echo $img_url_alt?>" id="jcrop_target" data-realwidth="<?php echo $img_info[0]?>" data-realheight="<?php echo $img_info[1]?>" style="max-width:100%;">
        <p><?php echo lang('ci:image_scaled_note')?></p>
    </div>

<br clear="all">
</div>
