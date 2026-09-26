<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 14:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\File;
use Zenbu\librairies\platform\ee\View;

class Grid {

	public static function processData($e, $grid_fields, $display_settings)
	{
		$data = [];

		if(count($grid_fields) > 0)
		{
			ee()->load->model('grid_model');
		}

		foreach($grid_fields as $grid_field)
		{
			$grid_columns = ee()->grid_model->get_columns_for_field($grid_field['field_id'], 'channel');
			$raw_grid_rows = ee()->grid_model->get_entry_rows($e->entry_id, $grid_field['field_id'], 'channel');
			foreach($raw_grid_rows as &$row)
			{
				if (is_array($row))
				{
					unset($row['params'], $row['fluid_field_data_id']);
				}
			}
			unset($row);
			
			$grid_rows = isset($raw_grid_rows[$e->entry_id]) && is_array($raw_grid_rows[$e->entry_id])
				? $raw_grid_rows[$e->entry_id]
				: [];
			
			foreach($grid_rows as $grid_row_key => $grid_row)
			{
				foreach($grid_row as $grid_col_key => $grid_col)
				{
					if(strncmp($grid_col_key, 'col_id_', 7) == 0)
					{
						$col_id = substr($grid_col_key, 7);
			
						$col_type = null;
			
						foreach ($grid_columns as $gc)
						{
							if ($gc['col_id'] == $col_id)
							{
								$col_type = $gc['col_type'];
								break;
							}
						}
			
						if($col_type == 'file')
						{
							$grid_rows[$grid_row_key][$grid_col_key] = File::get_thumb_path($grid_col);
						}
					}
				}
			}

			$data['field_id_'.$grid_field['field_id']] = View::render('columns/fieldtypes/grid.twig', ['rows' => $grid_rows, 'columns' => $grid_columns, 'entry_id' => $e->entry_id, 'field_id' => $grid_field['field_id']]);
			$data['raw_field_id_'.$grid_field['field_id']] = $grid_rows + ['columns' => $grid_columns];
		}

		return $data;
	}

}