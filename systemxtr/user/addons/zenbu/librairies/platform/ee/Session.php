<?php namespace Zenbu\librairies\platform\ee;

class Session
{
	public static function user()
	{
		$output = ee()->session->userdata;
		$output['id'] = ee()->session->userdata['member_id'];
		// EE6+: map group_id to primary_role_id for backwards compatibility
		if (! isset($output['group_id']) && isset($output['primary_role_id'])) {
			$output['group_id'] = $output['primary_role_id'];
		}
        return (object) $output;
	}

	public static function setFlash($key, $message = '')
	{
        return ee()->session->set_flashdata($key, $message);
	}

	public static function getFlash($key)
	{
        return ee()->session->flashdata($key);
	}

	public static function setCache($key, $data = FALSE)
	{
        return ee()->session->set_cache('zenbu', $key, $data);
	}

	public static function getCache($key)
	{
        return ee()->session->cache('zenbu', $key);
	}

	public static function getCsrfInput()
	{
		if(defined('CSRF_TOKEN'))
		{
			$output = '<input type="hidden" name="csrf_token" value="'.CSRF_TOKEN.'" />';
		}
		else
		{
			$output = '<input type="hidden" name="XID" value="'.ee()->security->restore_xid().'" />';
		}

		return $output;
	}

}