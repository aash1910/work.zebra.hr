<?php
// Default values if not set
$canvas_width   = isset($canvas_width)   ? $canvas_width   : '1000';
$canvas_height  = isset($canvas_height)  ? $canvas_height  : '1000';
$padding        = isset($padding)        ? $padding        : '0';
$quality        = isset($quality)        ? $quality        : '100';
$background_type = isset($background_type) ? $background_type : 'white';

$white_selected      = ($background_type == 'white')      ? 'selected="selected"' : '';
$transparent_selected = ($background_type == 'transparent') ? 'selected="selected"' : '';
?>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable" style="">
    <thead>
        <tr>
            <th>Canvas Width</th>
            <th>Canvas Height</th>
            <th>Padding</th>
            <th>Background Type</th>
            <th>Quality</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php echo form_input(
                    $action_field_name . '[canvas_width]',
                    $canvas_width,
                    'style="border:1px solid #ccc; width:80%;" type="number" min="1"'
                ); ?>
            </td>
            <td>
                <?php echo form_input(
                    $action_field_name . '[canvas_height]',
                    $canvas_height,
                    'style="border:1px solid #ccc; width:80%;" type="number" min="1"'
                ); ?>
            </td>
            <td>
                <?php echo form_input(
                    $action_field_name . '[padding]',
                    $padding,
                    'style="border:1px solid #ccc; width:80%;" type="number" min="0"'
                ); ?>
            </td>
            <td>
                <?php echo form_dropdown(
                    $action_field_name . '[background_type]',
                    array(
                        'white'       => 'White Background',
                        'transparent' => 'Transparent Background (PNG only)'
                    ),
                    $background_type
                ); ?>
            </td>
            <td>
                <?php echo form_input(
                    $action_field_name . '[quality]',
                    $quality,
                    'style="border:1px solid #ccc; width:80%;" type="number" min="0" max="100" step="1"'
                ); ?>
            </td>
        </tr>
    </tbody>
</table>

<div style="text-align: justify; margin-top: 10px;">
    <em>Normalize the image to a fixed canvas size with optional padding and background. Quality applies to JPEG output.</em>
</div>