<div id="visitor-sync" class="panel box">
    <?=form_open(ee('CP/URL', $baseUri . 'sync-members'))?>
    <div class="panel-body tbl-ctrls">
        <h1 class="panel-heading"><?=lang('v:sync_mdata')?></h1>

        <?=ee('CP/Alert')->get('shared-form')?>

        <div class="col-group">
            <div class="col w-6">
                <p><?=lang('v:sync_desc_0');?></p>
                <p><?=lang('v:sync_desc_1');?></p>
                <p><?=lang('v:sync_desc_2');?></p>
                <p><?=lang('v:sync_desc_3');?></p>
            </div>
            <div class="col w-1">&nbsp;</div>
            <div class="col w-9 setting-field">
                <div style="height:35px;">
                    <button type="button" class="btn" style="float:right;" onclick="$('#standardFieldsList input:checkbox').attr('checked', 'checked');">Select All</button>
                    <h3 style="padding-top:5px">Standard Fields</h3>
                </div>
                <div id="standardFieldsList" style="margin-left:15px;">
                    <?php foreach ($fields as $field) :
                        $selected = in_array($field, $fields_checked);
                        ?>
                    <div>
                        <label class="choice block <?=($selected ? 'chosen' : '')?>">
                            <input type="checkbox" name="fields[]" value="<?=$field?>" <?=($selected ? 'checked="checked"' : '')?>> <?=$field?>
                        </label>
                    </div>
                    <?php endforeach ?>
                </div>
                <br />

                <div style="height:35px;">
                    <button type="button" class="btn" style="float:right;display:inline-block;" onclick="$('#customFieldsList input:checkbox').attr('checked', 'checked');">Select All</button>
                    <h3 style="padding-top:5px">Custom Fields</h3>
                </div>
                <div id="customFieldsList" style="margin-left:15px;">
                    <?php foreach ($custom_fields as $field_id => $fieldName) :
                        $selected = in_array($field_id, $custom_fields_checked);
                        ?>
                    <div>
                        <label class="choice block <?=($selected ? 'chosen' : '')?>">
                            <input type="checkbox" name="custom_fields[]" value="<?=$field_id?>" <?=($selected ? 'checked="checked"' : '')?>> <?=$fieldName?>
                        </label>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <div class="form-btns">
            <input class="btn" type="submit" value="Start Sync">
        </div>
    </div>
    <?php form_close();?>
</div>
