/**
 * IPS UTF8 Converter
 * (c) 2013 Invision Power Services - http://www.invisionpower.com
 *
 *
 * Author: Matt "I'm no Rikki Tissier" Mecham
 */

// Our namespace
var ips = {};

!function($, window, document, _undefined)
{
	$.extend(ips,
	{
		_settings: {},

		boot: function (config) {
			_settings = config;
			
			if ( $('[data-init]').length )
			{
				var func = $('[data-init]').attr('data-init');
				this[func]();
			}
			
			$('body').on( 'click', 'a[data-confirm]', $.proxy( function(e) {
				return this.confirm( e );
			}, this ) );
			
			$('body').on( 'click', 'button[data-modal=submit]', $.proxy( function(e) {
				return this.modalSubmit( e );
			}, this ) );
		},
		
		/**
		 * Confirm modal
		 */
		confirm: function(e)
		{
			var elem = $(e.target).closest('[data-confirm]');
			var url  = $(elem).attr('href');
			
			$('#confirmModal').modal('show');
			$('#confirmModal').attr('data-url', url );
			$('#confirmModalText').html("Please confirm this action");
			$('button[data-modal=submit]').html("Confirm");
			return false;
		},
		
		/**
		 * Confirm modal
		 */
		modalSubmit: function(e)
		{
			var url = $('#confirmModal').attr('data-url');
			$('#confirmModal').modal('hide');
			
			window.location = url;
			
			return false;
		},
		
		/**
		 * Init the processing screen
		 */
		processInit: function()
		{
			 ips.process();
		},
		
		/**
		 * Main processing screen
		 *
		 */
		process: function()
		{
			/* Fire captain! */
			ips.ajax( $('body').attr('data-url') + '?controller=' + ips.getSetting('controller') + '&do=process', {}, function( data, status, xhr )
			{
				console.dir( data );
				
				if ( data[0] == 'completed' )
				{
					window.location = $('body').attr('data-url') + '?controller=' + ips.getSetting('controller') + '&do=completed';
				}
				else
				{
					$("#progressBar").css( 'width', data[1] + '%');
					$("#progressBar span").html( data[2] );
				}
				
				$('#message').html( data[2] );
				
				ips.process();
			}, { 'type': 'GET' } );
		},
	
		/**
		 * Ajax Wrapper. POSTs and expects JSON
		 *
		 * @param string 	URL
		 * @param object 	POST data
		 * @param function  Callback
		 * @param object 	Additional options to override or extend defaults
		 *
		 * @return XMLHttpRequest
		 */
		ajax: function( url, data, success, options )
		{
			var ajaxOptions = $.extend(true,
			{
				data: 	  data,
				url: 	  url,
				timeout:  ips.getSetting('maxExecTime') !== false ? parseInt( ips.getSetting('maxExecTime') * 1000 ) : 30000,
				cache:	  false,
				success:  function( data, textStatus, jqXHR )
						  {
							success( data, textStatus, jqXHR );
						  },
				type: 	  'POST',
				dataType: 'json',
				error: function(xhr, textStatus, errorThrown)
				{
					try
					{
						/* If its JSON, run it anyway */
						success.call(null, $.parseJSON(xhr.responseText), textStatus);
					}
					catch (e)
					{
						/* Throw error */
						ips.serverError(xhr, textStatus, errorThrown);
					}
				}
			}, options);

			return $.ajax(ajaxOptions);
		},
		
		/**
		 * Server error
		 */
		serverError: function( xhr, responseText, errorThrow )
		{
			if ( responseText === 'timeout' )
			{
				var counter = ( ips.getUrlParam('count') === false ) ? 0 : parseInt( ips.getUrlParam('count') );
			
				if ( counter < 101 )
				{
					window.location = $('body').attr('data-url') + '?controller=' + ips.getSetting('controller') + '&do=process&count=' + ( counter + 1 );
					return;
				}
			
				$('#confirmModal').modal('show');
				$('button[data-modal=submit]').html("Continue");
				$('#confirmModal').attr('data-url', $('body').attr('data-url') + '?controller=' + ips.getSetting('controller') + '&do=process' );
				$('#confirmModalText').html("The server encountered multiple instances where it has stopped responding.<br><br>Press 'Continue' to reload the page and continue the conversion.<br>Press 'Cancel' to pause the conversion so that you may contact technical support.");
			}
			
			console.error( xhr.responseText );
		},
		
		getUrlParam: function( name )
		{
			if ( name = ( new RegExp( '[?&]'+encodeURIComponent( name ) + '=([^&]*)' ) ).exec( location.search ) )
			{
				return decodeURIComponent( name[1] );
			}
			
			return false;
	  	},
	  	
		/**
		 * Config getter
		 *
		 * @param 	{string} 	key 	Setting key to return
		 * @returns {mixed} 	Config setting, or undefined if it doesn't exist
		 */ 
		getSetting: function (key) {
			return ( _settings.hasOwnProperty( key ) ) ?  _settings[ key ] : false;
		}

	} );
	
	ips.boot( ipsSettings );

}(jQuery, this, document);