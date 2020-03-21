(function (ajax_data){

	var actionButton = jQuery('#btn-activate-migration');
	var loader = jQuery('#migration-loader');

	function startMigration() {
		jQuery.ajax({

			url: ajax_data.ajax_url,
			type: "post",
			timeout: 0,
			data: {action: 'start_migration', security: ajax_data.security},
			beforeSend: function() {
				actionButton.attr('disabled', true);
				loader.show();
			},
			success: function(response) {
				actionButton.removeAttr('disabled');
				loader.hide();
				console.log(response);
			},
			error: function(error) {
				actionButton.removeAttr('disabled');
				loader.hide();
				console.log(error);
			}
		
		});
	 }

	/*if(actionButton) {
	 	actionButton.click(startMigration);
	}*/
	 	
	
}(primicias_ajax_object));