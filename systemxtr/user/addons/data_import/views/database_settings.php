<h3><?= lang('database_settings') ?></h3>

<form method="post" action="<?= ee('CP/URL')->make('addons/settings/data_import/database_settings') ?>" class="form">
	<?= ee('CP/Alert')->getAllInlines() ?>
	<input type="hidden" name="XID" value="<?= ee()->csrf->get_user_token() ?>">
	
	<div class="box">
		<div class="form-group">
			<label><?= lang('hostname') ?></label>
			<input type="text" name="hostname" value="<?= isset($hostname) ? ee('Format')->make('Text', $hostname)->convertToEntities() : '' ?>" class="form-control">
		</div>
		
		<div class="form-group">
			<label><?= lang('username') ?></label>
			<input type="text" name="username" value="<?= isset($username) ? ee('Format')->make('Text', $username)->convertToEntities() : '' ?>" class="form-control">
		</div>
		
		<div class="form-group">
			<label><?= lang('password') ?></label>
			<input type="password" name="password" value="<?= isset($password) ? ee('Format')->make('Text', $password)->convertToEntities() : '' ?>" class="form-control">
		</div>
		
		<div class="form-group">
			<label><?= lang('database') ?></label>
			<input type="text" name="database" value="<?= isset($database) ? ee('Format')->make('Text', $database)->convertToEntities() : '' ?>" class="form-control">
		</div>
	</div>
	
	<div class="form-btns">
		<button type="submit" name="submit" value="submit" class="btn btn-primary"><?= lang('submit') ?></button>
	</div>
</form>