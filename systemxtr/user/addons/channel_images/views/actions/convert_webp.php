<?php
// Default values (in case they're not set)
$quality = isset($quality) ? $quality : '80';
?>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable" style="">
    <thead>
        <tr>
            <th><?php echo lang('ci:webp:quality') ?: 'Quality'; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php echo form_input(
                    $action_field_name . '[quality]',
                    $quality,
                    'style="border:1px solid #ccc; width:80%;" min="0" max="100" step="1" type="number"'
                ); ?>
            </td>
        </tr>
    </tbody>
</table>

<div style="text-align: justify">
    <em>WebP quality (0-100). Lower = smaller file, higher = better quality. Recommended: 80</em>
</div>