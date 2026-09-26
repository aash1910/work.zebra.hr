<form method="post" action="<?= ee('CP/URL')->make('addons/settings/data_import/add_import_item') ?>" class="form">
	<?= ee('CP/Alert')->getAllInlines() ?>
	<input type="hidden" name="XID" value="<?= ee()->csrf->get_user_token() ?>">
	
	<div class="form-group">
		<label><?= lang('new_import_item') ?></label>
		<input type="text" name="title" value="" style="width:150px" class="form-control">
		<button type="submit" name="submit" value="submit" class="btn btn-primary"><?= lang('submit') ?></button>
	</div>
</form>

<div class="box">
	<div class="tbl-wrap">
		<table class="table">
			<thead>
				<tr>
					<th style="width:40%"><?= lang('preference') ?></th>
					<th><?= lang('settings') ?></th>
					<th><?= lang('remove') ?></th>
					<th><?= lang('proceed') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($import_list as $value): ?>
				<tr>
					<td><a href="#" class="import_list_item" id="<?= $value['import_id'] ?>"><?= ee('Format')->make('Text', $value['title'])->convertToEntities() ?></a></td>
					<td><a href="<?= ee('CP/URL')->make('addons/settings/data_import/settings/'.$value['import_id']) ?>"><?= lang('settings') ?></a></td>
					<td><a href="<?= ee('CP/URL')->make('addons/settings/data_import/remove_import_item', ['import_id' => $value['import_id']]) ?>" class="delete_link"><?= lang('remove') ?></a></td>
					<td><a href="<?= ee('CP/URL')->make('addons/settings/data_import/do_import', ['import' => $value['title']]) ?>"><?= lang('proceed') ?></a></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
