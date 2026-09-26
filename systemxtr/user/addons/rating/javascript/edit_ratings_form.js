(function($){
	$('select[name^=visible_fields]').change(function()
	{
		$('select[name^=visible_fields] option').each(function(i)
		{
			var $that = $(this);
			if ($that.is(":selected"))
			{
				$('tr[id$="-rating_field_' + $that.val()+'"]').show();
			}
			else
			{
				$('tr[id$="-rating_field_' + $that.val()+'"]').hide();
			}
		});
	});
}(jQuery));