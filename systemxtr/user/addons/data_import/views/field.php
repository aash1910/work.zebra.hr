			<tr>
				<td class="store_ft_text"><?= lang('import') ?></td>
				<td class="store_ft_text">
					<input type="checkbox" name="data_entry_id" value="<?= ee('Format')->make('Text', $entry_id)->convertToEntities() ?>" <?= $is_entry ? 'checked="checked"' : '' ?>>
				</td>
			</tr>
