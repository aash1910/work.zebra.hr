!(function($, global){
	"use strict";

	var Rating = global.Rating = global.Rating || {};

	// --------------------------------------------------------------------

	/**
	 * Short Name - Converts a phrase to a url friendly slug
	 *
	 * @access	public
	 * @param  {String}		a	string to convert
	 * @return {String}			converted slug
	 */

	Rating.shortName = function(a)
	{
		var b = "",
			c = "_",
			f = "",
			d = {
				"223": "ss",
				"224": "a",
				"225": "a",
				"226": "a",
				"229": "a",
				"227": "ae",
				"230": "ae",
				"228": "ae",
				"231": "c",
				"232": "e",
				"233": "e",
				"234": "e",
				"235": "e",
				"236": "i",
				"237": "i",
				"238": "i",
				"239": "i",
				"241": "n",
				"242": "o",
				"243": "o",
				"244": "o",
				"245": "o",
				"246": "oe",
				"249": "u",
				"250": "u",
				"251": "u",
				"252": "ue",
				"255": "y",
				"257": "aa",
				"269": "ch",
				"275": "ee",
				"291": "gj",
				"299": "ii",
				"311": "kj",
				"316": "lj",
				"326": "nj",
				"353": "sh",
				"363": "uu",
				"382": "zh",
				"256": "aa",
				"268": "ch",
				"274": "ee",
				"290": "gj",
				"298": "ii",
				"310": "kj",
				"315": "lj",
				"325": "nj",
				"352": "sh",
				"362": "uu",
				"381": "zh"
			};

		if (b !== "")
		{
			if (a.substr(0, b.length) == b)
			{
				a = a.substr(b.length);
			}
		}

		a = a.toLowerCase();
		b = 0;

		for (var g = a.length; b < g; b++)
		{
			var e = a.charCodeAt(b);

			if (e >= 32 && e < 128)
			{
				f += a.charAt(b);
			}
			else if (d.hasOwnProperty(e))
			{
				f += d[e];
			}
		}

		d = new RegExp(c + "{2,}", "g");
		a = f;
		a = a.replace("/<(.*?)>/g", "");
		a = a.replace(/\s+/g, c);
		a = a.replace(/\//g, c);
		a = a.replace(/[^a-z0-9\-\_]/g, "");
		a = a.replace(/\+/g, c);
		a = a.replace(d, c);
		a = a.replace(/-$/g, "");
		a = a.replace(/_$/g, "");
		a = a.replace(/^_/g, "");
		a = a.replace(/^-/g, "");
		a = a.replace(/\.+$/g, "");
		return a;
	};
	//END Rating.shortName

	// --------------------------------------------------------------------

	/**
	 * Auto Generate Short Name
	 *
	 * @access	public
	 * @param	{Object}	$label						label to convert
	 * @param	{Object}	$name						name field to fill
	 * @return	{void}
	 */

	Rating.autoGenerateShortname = function($label, $name)
	{
		var autoShorty	= true;
		$name.keyup(function(){
			autoShorty	= false;
		});
	
		//generate on each keyup because... because.
		$label.keyup(function(){
			if (autoShorty) {
				$name.val(Rating.shortName($label.val()));
			}
		}).keyup();
	};
	
	//END Rating.autoGenerateShortname

	// --------------------------------------------------------------------
	//	document ready handler
	// --------------------------------------------------------------------

	$(function(){
		var $context = $('form.settings');

		// -------------------------------------
		//	field type switcher
		// -------------------------------------
		
		var $field_type	= $context.find('select[name="field_type"]');
		$($field_type).change(function(event){
			var def	= ($(this).val() == 'number') ? 10: 255;
			if ($context.find('input[name="field_id"]').val() == '' && $context.find('input[name="field_maxl"]').length > 0) {
				$context.find('input[name="field_maxl"]').val(def);
			}
		});

		// -------------------------------------
		//	message variable whammer jammer
		// -------------------------------------
		
		$('.message_variable').click(function(event){
			event.preventDefault();
			$('textarea[name="message"]').val($('textarea[name="message"]').val() + $(this).text());
		});

		// -------------------------------------
		//	autoshortname
		// -------------------------------------

		var $label		= $context.find('input[name$="_label"]');
		var $name		= $context.find('input[name$="_name"]');

		if (typeof Rating !== 'undefined' &&
			$label.length > 0 &&
			$name.length > 0 &&
			$name.val() == '')
		{
			Rating.autoGenerateShortname($label, $name);
		}
	});
	//END $(function(){

}(jQuery, window));
