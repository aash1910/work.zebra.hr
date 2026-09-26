$(document).ready(function(){

	/*	
		RATING SUBMIT FORM
	*/
	$('form.ratingForm').on('submit', function(e){
		e.preventDefault();
		var ratingForm = $(this);
		var url = ratingForm.attr('action');
		var entryId = ratingForm.attr('id');
		entryId = entryId.replace('rating_form','');
		$('.ratingFormDiv').html('<img src="{exp:rating:theme_folder_url}code_pack/images/loading.gif"> Submitting. Please wait...');

		if (url !== null) {
			$.ajax({
				type: "POST",
				url: url,
				data: ratingForm.serialize(),
				success: function(response) {
					$('.ratingFormDiv').html('<p><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> ' + 'Your rating/review has been successfully submitted!' + '</p>').fadeOut(5000);
					window.location.reload();
				},
				error: function(response) {
					console.log('ERROR:' + response.responseJSON.message);
					$('.ratingFormDiv').html('<p>' + response.responseJSON.message + '</p>');
				}
			});
		} // if

	}); // rating submit form
	/* --------------------------------------------- */


	/*
		RATING EDIT FORM
	*/
	$('a.ratingEdit').on('click', function(e){
		e.preventDefault();
		var ratingId = $(this).closest('li').attr('id');
		var ratingItem = '#' + ratingId;
		var ratingItemShowForm = $(ratingItem + ' div.ratingEditShowForm');
		var url = $(this).attr('href');
		
		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					$(ratingItemShowForm).html('<div class="panel-body">' + results + '</div>');

					// Update
					$('form.ratingForm').on('submit', function(e){
						e.preventDefault();
						var ratingForm = $(this);
						var url = ratingForm.attr('action');
						var entryId = ratingForm.attr('id');
						entryId = entryId.replace('rating_form','');
						$('.ratingEditBtn').html('<img src="{exp:rating:theme_folder_url}code_pack/images/loading.gif"> Updating. Please wait...');

						if (url !== null) {
							$.ajax({
								type: "POST",
								url: url,
								data: ratingForm.serialize(),
								success: function(data) {
									window.location.reload();
								},
								error: function(data) {
									console.log('ERROR:' + data);
								}
							});
						} // if

					});

				},
				error: function(results) {
					console.log('ERROR:' + results);
				}
			});
		}

	}); // rating edit form
	/* --------------------------------------------- */


	/*
		COMMENT EDIT FORM
	*/
	// Edit
	$('a.ratingCommentEdit').on('click', function(e){
		e.preventDefault();
		var ratingCommId = $(this).closest('div.ratingCommentItem').attr('id');
		var ratingCommItem = '#' + ratingCommId;
		var putBack = $(ratingCommItem);
		var url = $(this).attr('href');

		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					$(ratingCommItem).html(results);

					// Update Comment
					$('form.ratingCommentForm').on('submit', function(e){
						e.preventDefault();
						var ratingCommentForm = $(this);
						var url = ratingCommentForm.attr('action');
						var entryId = ratingCommentForm.attr('id');
						entryId = entryId.replace('rating_form','');
						
						if (url !== null) {
							$.ajax({
								type: "POST",
								url: url,
								data: ratingCommentForm.serialize(),
								success: function(data) {
									window.location.reload();
								},
								error: function(data) {
									console.log(data);
									$(ratingCommItem).html('<p>' + data.responseJSON.message + '</p>');
								}
							});
						} // if
					});

				},
				error: function(results) {
					console.log('ERROR:' + results);
				}
			});
		}

	}); // comment edit form
	/* --------------------------------------------- */


	/*	
		RATING DELETE
	*/
	$('a.ratingDelete').on('click', function(e){
		e.preventDefault();
		var ratingId = $(this).closest('li.ratingItem').attr('id');
		var ratingItem = '#' + ratingId;
		var url = $(this).attr('href');
		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					console.log(results.message);
					$(ratingItem).html('<div class="alert alert-success" role="alert"><p>Review has been successfully removed!</p></div>');
				},
				error: function(results) {
					console.log('ERROR:' + results);
				}

			});
		} // if

	}); // rating delete
	/* --------------------------------------------- */


	/*	
		RATING DELETE ALL
	*/
	$('a.ratingsDeleteAll').on('click', function(e){
		e.preventDefault();
		var url = $(this).attr('href');
		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					$('.ratingEntries').html('<div class="alert alert-success" role="alert"><p>All reviews have been successfully removed!</p></div>').fadeOut(5000);
					window.location.reload();
				},
				error: function(results) {
					console.log('ERROR:' + results);
				}

			});
		} // if

	}); // ratings delete all
	/* --------------------------------------------- */


	/*	
		RATING COMMENT DELETE
	*/
	$('a.ratingCommentDelete').on('click', function(e){
		e.preventDefault();
		var ratingCommId = $(this).closest('div.ratingCommentItem').attr('id');
		var ratingCommItem = '#' + ratingCommId;
		var url = $(this).attr('href');
		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					$(ratingCommItem).html('<div class="alert alert-success" role="alert"><p>Comment has been successfully removed!</p></div>');
				},
				error: function(results) {
					console.log('ERROR:' + results);
				}

			});
		} // if

	}); // rating comment delete
	/* --------------------------------------------- */


	/*	
		VOTE BUTTONS
	*/
	$('a.ratingVote').on('click', function(e){
		e.preventDefault();
		var ratingVote = $(this);
		var ratingVoteSuccess = $('div.ratingVoteSuccess');
		var url = ratingVote.attr('href');
		var htp	= /^(https?|ftp):\/\//;
		var segments = url.replace(htp, '').split('/');
		var seg = segments[4]; // Dependent on your URI structure. The segment with the Entry ID.

		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					url = '{path="rating_inc/ajax-vote-results"}/' + seg;
					$.ajax({
						url: url,
						success: function(res) {
							ratingVote.closest('.refreshVoteData').html('<div class="refreshVoteData pull-right text-right">' + 
								'<span class="votesuccess text-success">Voted!</span><br />' + res + '</div>').fadeIn('fast');
							$('span.votesuccess').fadeOut(5000);
						},
						error: function(res) {
							console.log('ERROR:' + res);
						}
					});
				},
				error: function(results) {
					console.log('ERROR:' + results);
				}
			});
		} // if
	}); // vote buttons
	/* --------------------------------------------- */


	/*	
		RATING FLAG
	*/
	$('a.ratingFlag').on('click', function(e){
		e.preventDefault();
		var ratingFlag = $(this);
		var url = ratingFlag.attr('href');
		if (url !== null) {
			$.ajax({
				url: url,
				success: function(results) {
					ratingFlag.before('<p class="flagsuccess text-success">' + results.message + '</p>').fadeIn('fast');
					ratingFlag.hide();
					$('p.flagsuccess').fadeOut(5000);
				},
				error: function(results) {
					console.log('ERROR:' + results);
				}
			});
		} // if

	}); // rating flag
	/* --------------------------------------------- */

}); // document ready