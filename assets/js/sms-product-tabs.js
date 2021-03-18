jQuery(document).ready(function() {
	
	jQuery( '#add_another_sms_tab' ).on( 'click' , function( e ) {
		
		// setup variables for use in generating a new tab
		var clone_container = jQuery( '#duplicate_this_row_sms' );
		var before_add_count = jQuery( '#number_of_tabs_sms' ).val();
		var new_count = parseInt(jQuery( '#number_of_tabs_sms' ).val())+parseInt(1); /* get new number of cloned element */
		var remove_tab_button = jQuery( '#duplicate_this_row_sms .number_of_tabs_sms' );
		var move_tab_content_buttons = jQuery( '#duplicate_this_row_sms .button-holder-sms' );
			
		clone_container.children( 'p' ).each(function() {
			
			jQuery(this).clone().insertBefore( '#duplicate_this_row_sms' ).removeClass('hidden_duplicator_row_title_field_sms').removeClass('hidden_duplicator_row_content_field_sms').addClass('new_duplicate_row_sms');
		
		}).promise().done(function() {
						
			jQuery( '.new_duplicate_row_sms' ).find('input').each(function() {
				if ( jQuery(this).is('input[name="hidden_duplicator_row_title"]') ) {
					jQuery(this).attr( 'name' , '_ipeir_wc_custom_repeatable_product_tabs_tab_title_'+new_count ).attr( 'id' , '_ipeir_wc_custom_repeatable_product_tabs_tab_title_'+new_count ).parents('p').addClass('_ipeir_wc_custom_repeatable_product_tabs_tab_title_'+new_count+'_field').removeClass('hidden_duplicator_row_title_field_sms').find('label').removeAttr('for').attr('for','_ipeir_wc_custom_repeatable_product_tabs_tab_title_'+new_count+'_field');
				}
			});
			
			jQuery( '.new_duplicate_row_sms' ).find('select').each(function() {
				if ( jQuery(this).is('select[name="hidden_duplicator_row_content[]"]') ) {
					jQuery(this).attr( 'name' , '_ipeir_wc_custom_repeatable_product_tabs_tab_content_'+new_count+'[]' ).attr( 'id' , '_ipeir_wc_custom_repeatable_product_tabs_tab_content_'+new_count ).parents('p').addClass('_ipeir_wc_custom_repeatable_product_tabs_tab_content_'+new_count+'_field').removeClass('hidden_duplicator_row_content_field_sms').find('label').removeAttr('for').attr('for','_ipeir_wc_custom_repeatable_product_tabs_tab_content_'+new_count+'_field');	
				}
			});
			
			// set the new value
			jQuery( '#number_of_tabs_sms' ).val(new_count);	
			// append the divider, between tab data
			jQuery( '.new_duplicate_row_sms' ).first().before( '<div class="ipeir-woo-custom-tab-divider"></div>' );
			
		});
		
		move_tab_content_buttons.clone().insertAfter( jQuery( '.ipeir-woo-custom-tab-divider').last() ).addClass( 'last-button-holder-sms' );	
	
		remove_tab_button.clone().prependTo( '.last-button-holder-sms' ).removeAttr( 'style' );
		jQuery( '.last-button-holder-sms' ).removeAttr( 'alt' ).attr( 'alt' , new_count );
		
		setTimeout(function() {
			jQuery( '.last-button-holder-sms' ).removeClass( 'last-button-holder-sms' );
			jQuery( '.new_duplicate_row_sms' ).removeClass( 'new_duplicate_row_sms' );
		},100);
		
		e.preventDefault();
		
	});
	// end duplicate tab
	
	/*
		Remove a new tab
	*/
	jQuery( 'body' ).on( 'click' , '.number_of_tabs_sms' , function( e ) {
		
		// setup our variables for use in tab removal
		var clicked_button = jQuery( this );				
		var tab_title_to_remove = jQuery( this ).parents( '.button-holder-sms' ).next();
		var tab_content_to_remove = jQuery( this ).parents( '.button-holder-sms' ).next().next();
		var divider_to_remove = jQuery( this ).parents( '.button-holder-sms' ).prev();
		var before_remove_count = jQuery('#number_of_tabs_sms').val();
		var count_post_remove = parseInt( jQuery('#number_of_tabs_sms').val() )-parseInt(1); /* get new number of cloned element */
		
		tab_title_to_remove.remove();
		tab_content_to_remove.remove();
		divider_to_remove.remove();
		clicked_button.parents( '.button-holder-sms' ).remove();
		
		
		
		e.preventDefault();
		
	});
	// end remove
	

	/* 
		How To Click 
		- slide out display
	*/
	jQuery( '.ipeir-tabs-how-to-toggle' ).on( 'click' , function( e ) {
		jQuery( '.ipeir-woo-tabs-hidden-how-to-info' ).slideToggle( 'fast' );
	});
	
});