;(function(global, $){
    //es5 strict mode
    "use strict";

    var ChannelImages = global.ChannelImages = global.ChannelImages || {};

    // internal, MCP
    var cimcp = $('#cimcp');

    if (cimcp.length > 0) {
        var imageIds = [];
        var imageIdsDone = [];
        var imageIdsCount = 0;
        var fieldIds = [];
        var imageAction;
        var actionTransferParams;
        var actionRegenParams;
        var imageFilters = cimcp.find('.image-filters');
        var imageActionToggler = cimcp.find('.action-toggler');
        var imageTransferToggler = cimcp.find('.transfer-toggle');
        var imageProgressHolder = cimcp.find('.progress_holder');
        initMCP();
    }

	$('.ImportMatrixImages .submit').click(function(e){
		importMatrixImages(e, true);
		return false;
	});

	/*$('.ImportMatrixImages .submit').click(function(){
		alert("submitting");
		return false;
	});*/

	$('.ImportMatrixImages').delegate('a.show_ajax_error', 'click', function(e){
		$('#ci_ajax_error').show().find('iframe').contents().find('html').html($(e.target).data('ajax_error'));
		return false;
	});

    // ----------------------------------------------------------------------
	// function from old js
	function importMatrixImages(e, initial = false) {
		var target = jQuery(e.target);
		var parent = target.closest('form');

        if (initial === true) {
            parent.find('.CI_IMAGES').find('.Image').removeClass('label-important label-working label-import-success').addClass('Queued');
        }

        var Current = parent.find('.CI_IMAGES').find('.Queued:first');
		var Params = parent.find(':input').serializeArray();

		console.log('importMatrixImages', initial, ChannelImages.AJAX_URL);

		if (Current.length === 0) return false;

		Params.push({name: 'ajax_method', value:'import_matrix_images'});
		Params.push({name: 'entry_id', value:Current.attr('rel')});
		Params.image_id = Current.attr('rel');

		Current.removeClass('Queued label-import-success label-important').addClass('label-working');
		target.html('Importing, please wait...');
		parent.find('.errormsg').html('');

		$.ajax({
			type: 'POST',
			url: ChannelImages.AJAX_URL,
			dataType: 'json',
			data: Params,
			success: function(rData) {
                console.log('Success', rData);
				if (rData.success == 'yes')	{
					importMatrixImages(e);
					Current.removeClass('label-working').addClass('label-import-success');
				}
				else{
					Current.removeClass('label-working').addClass('label-important');
					parent.find('.errormsg').html('<strong style="color:red">'+rData.body+'</strong>');
				}

                console.log('Changing Button Text to: Import');
				target.html('Import');
			},
			error: function(xhr, textStatus, errorThrown) {
                console.log(textStatus, errorThrown, xhr);
				Current.removeClass('label-working label-working').addClass('label-important');
				target.html('Import');
				parent.find('.errormsg').html('<strong style="color:red">AJAX Error!<a href="#" class="show_ajax_error">&nbsp;&nbsp;&nbsp;Click here to display the server response</strong>').find('a').data('ajax_error', xhr.responseText);
			}
		});


	};

    function initMCP() {
        cimcp.find('.select2').select2();
        cimcp.find('.start_actions').click(startAction);
        imageFilters.on('keyup change', reloadImages).trigger('change');
        imageActionToggler.on('click', toggleAction).find('input:checked').trigger('click');
        imageTransferToggler.on('click', toggleTransferType).find('input:checked').trigger('click');
        imageProgressHolder.on('click', '.show_error', showActionError);
    }

    // ----------------------------------------------------------------------

    function reloadImages(e) {
        if (e.type == 'change' && e.target.nodeName == 'INPUT') return;

        var params = imageFilters.find(':input').serializeArray();
        params.push({name:'ajax_method', value:'load_batch_images'});
        params.push({name:'site_id', value:ChannelImages.site_id});
        params.push({name:'XID', value:EE.XID});

        $.ajax({url:ChannelImages.AJAX_URL, type:'POST', dataType:'json', data:params,
            crossDomain: true,
            success: function(rdata){
                imageIds = rdata.ids;
                imageIdsCount = imageIds.length;
                fieldIds = rdata.field_ids;

                cimcp.find('.total_count').html(imageIdsCount);

                getImageSizes();
            },
            error: function(){

            }

        });
    }

    // ----------------------------------------------------------------------

    function getImageSizes() {
        cimcp.find('.action-regen .image_sizes').empty();

        var params = [];
        params.push({name:'ajax_method', value:'get_image_sizes'});
        params.push({name:'field_ids', value:fieldIds.join(',')});
        params.push({name:'XID', value:EE.XID});

        $.ajax({url:ChannelImages.AJAX_URL, type:'POST', dataType:'json', data:params,
            success: function(rdata){
                if (!rdata.fields) return;

                var html = '';

                for (var i = 0; i < rdata.fields.length; i++) {
                    html += ChannelImages.Templates.mcp_regen_fieldsizes(rdata.fields[i]);
                }

                cimcp.find('.action-regen .image_sizes').html(html);
            },
            error: function(){

            }

        });
    }

    // ----------------------------------------------------------------------

    function toggleAction(e) {
        imageAction = imageActionToggler.find('input:checked').val();

        cimcp.find('.actions').hide();
        cimcp.find('.action-'+imageAction).show();
    }

    // ----------------------------------------------------------------------

    function toggleTransferType(e) {
        var value = imageTransferToggler.find('input:checked').val();

        cimcp.find('tbody.options').hide();
        cimcp.find('tbody.option-'+value).show();
    }

    // ----------------------------------------------------------------------

    function startAction(e, tr) {

        if (!actionTransferParams && imageAction == 'transfer') {
            actionTransferParams = cimcp.find('.action-transfer').find(':input').serializeArray();
        } else {
            actionRegenParams = cimcp.find('.action-regen').find(':input').serializeArray();
        }

        if (e) {
            var target = $(e.target);
            if (target.hasClass('start_actions') && !target.hasClass('work')) {
                target.addClass('work');
            }
        }

        for (var i = 0; i < imageIds.length; i++) {
            if (!imageIds[i]) continue;

            if (!imageIds[i].done) {

                if (!tr) {
                    tr = imageProgressHolder.find('.current-actions').append('<tr></tr>').find('tr:last');
                }

                execAction(i, tr);
                return;
            }
        }

        // If we arrived here, that means we are done!
        updateActionRow(null, tr, true);
    }

    // ----------------------------------------------------------------------

    function execAction(index, tr) {
        var params = [];

        if (imageAction == 'transfer') {
            params = cimcp.find('.action-transfer').find(':input').serializeArray();
        } else {
            params = cimcp.find('.action-regen').find(':input').serializeArray();
        }

        params.push({name:'id', value:imageIds[index].id});
        params.push({name:'action', value:imageAction});
        params.push({name:'ajax_method', value:'exec_batch'});
        params.push({name:'XID', value:EE.XID});

        imageIds[index].done = true;
        imageIds[index].loading = true;

        updateActionRow(index, tr);

        $.ajax({url:ChannelImages.AJAX_URL+'&image_id='+imageIds[index].id, type:'POST', dataType:'json', data:params,
            success: function(rdata){
                postAction(index, tr);
            },
            error: function(xhr){
                postAction(index, tr, xhr);
            }
        });
    }

    // ----------------------------------------------------------------------

    function postAction(index, tr, xhr) {
        var percent;

        if (!xhr) {
            imageIdsDone.push(imageIds[index].id);
            percent = (100/imageIdsCount) * imageIdsDone.length;
            imageProgressHolder.find('.progress').css('width', percent+'%').find('.total_done').html(imageIdsDone.length);
            startAction(null, tr);

            delete imageIds[index];
            return;
        }



        $.ajax({url:ChannelImages.AJAX_URL, type:'POST', dataType:'json', data:{ajax_method: 'get_image_details', id:imageIds[index].id, XID: EE.XID},
            success: function(rdata){
                imageIds[index].entry = rdata.entry;
                imageIds[index].channel = rdata.channel;
                imageIds[index].field = rdata.field;
                imageIds[index].image = rdata.image;
                imageIds[index].ajax_error = xhr.responseText;
                imageIds[index].loading = false;

                if (!imageIds[index].retry_count) {
                    imageIds[index].retry_count = 3;
                } else {
                    imageIds[index].retry_count--;
                }

                if (imageIds[index].retry_count) imageIds[index].retry = true;
                else imageIds[index].retry = false;

                updateActionRow(index, tr);

                if (imageIds[index].retry) {
                    setTimeout(function(){
                        imageIds[index].ajax_error = false;
                        execAction(index, tr);
                    }, 3000);
                }
            }
        });

    }

    // ----------------------------------------------------------------------

    function updateActionRow(index, tr, done) {
        var obj;

        if (done) {
            obj = {action_done: true};
        } else {
            obj = imageIds[index];
        }

        // Just in case
        if (!tr) {
            tr = imageProgressHolder.find('.current-actions').find('tr:last');
        }

        tr.html(ChannelImages.Templates.mcp_batch_action_row(obj));
    }

    // ----------------------------------------------------------------------

    function showActionError(e) {
        var error = $(e.target).closest('.action_error').find('script').html();
        $('#ci_ajax_error').show().find('iframe').contents().find('html').html(error);
    }

    // ----------------------------------------------------------------------

}(window, jQuery));