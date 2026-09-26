<?php ?>
<div class="box panel" >
     <div class="box sidebar panel-heading">
        <h1><?php echo lang('ci:import')?></h1>
    </div>
    <div class="settings panel-body">
        <div id="cimcp">
            <div class="ci-body">

<?php
if (isset($fields) == true) :
    foreach ($fields as $field) :
        echo form_open($baseUrl.'/import_images');
        echo form_hidden('field[type]', $field['type']);
        echo form_hidden('field[field_id]', $field['field_id']);
        echo form_hidden('field[channel_id]', $field['channel_id']);
        ?>
            <fieldset class="col-group">
                <div class="setting-field col-lg-8 last">
                    <table class="mainTable ImportMatrixImages">
                        <thead>
                            <tr>
                                <th colspan="3"><?php echo $field['field_label']?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php echo lang('ci:transfer_field')?></strong></td>
                                <td>
                                    <select name="field[ci_field]">
                                    <?php foreach ($field['ci_fields'] as $ci) : ?>
                                        <option value="<?php echo $ci->field_id?>"><?php echo $ci->field_label?></option>
                                    <?php endforeach;?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                            if ($field['type'] == 'matrix') :
                                ?>
                            <tr>
                                <td><strong><?php echo lang('ci:column_mapping')?></strong></td>
                                <td>
                                    <table class="mainTable">
                                    <thead>
                                        <tr>
                                            <th colspan="3"><?php echo lang('ci:column_mapping')?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($field['cols'] as $col) :
                                        ?>
                                        <tr>
                                            <td><?php echo $col->col_label?></td>
                                            <td>
                                                <select name="field[fieldmap][<?php echo $col->col_id?>]">
                                                    <option value=""><?php echo lang('ci:dont_transfer')?></option>
                                                    <option value="image"><?php echo lang('ci:image')?></option>
                                                    <option value="title"><?php echo lang('ci:title')?></option>
                                                    <option value="description"><?php echo lang('ci:desc')?></option>
                                                    <option value="category"><?php echo lang('ci:category')?></option>
                                                    <option value="cifield_1"><?php echo lang('ci:cifield_1')?></option>
                                                    <option value="cifield_2"><?php echo lang('ci:cifield_2')?></option>
                                                    <option value="cifield_3"><?php echo lang('ci:cifield_3')?></option>
                                                    <option value="cifield_4"><?php echo lang('ci:cifield_4')?></option>
                                                    <option value="cifield_5"><?php echo lang('ci:cifield_5')?></option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach;?>
                                    </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php endif;?>
                            <tr>
                                <td style="width:300px"><?php echo lang('ci:import_entries')?></td>
                                <td class="CI_IMAGES">
                                    <?php
                                    if (isset($field['entries']) == true) :
                                        foreach ($field['entries'] as $row) :
                                            ?>
                                        <div class="Image Queued label" rel="<?php echo $row->entry_id?>" style="float:left;margin:0 5px 5px 0;"><?php echo $row->entry_id?></div>
                                            <?php
                                        endforeach;
                                    endif;
                                    ?>
                                    <br clear="all">
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><button class="btn submit">Import</button></td>
                                <td class="errormsg"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </fieldset>
        <?php echo form_close()?>
    <?php endforeach; ?>
    &nbsp;
<?php endif; ?>
        </div> <!-- </ci-body> -->
    </div>
</div>

<?php
$this->embed('mcp/_footer', array());
?>
