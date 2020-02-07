$(function(){
	
	$('.no_js_hide').show();
	
	var redirectStep = function( infoBox, url, baseurl ) {
	
		$.ajax( url, {
			dataType: 'json',
			cache:	  false,
			timeout:  IPS_TIMEOUT !== false ? parseInt( IPS_TIMEOUT * 1000 ) : 30000,
			complete: function( jqXHR, textStatus )
			{
				var response = $.parseJSON( jqXHR.responseText );

				if( response.redirect )
				{
					window.location = response.redirect;
				}
				
				infoBox.children('span').html( response[1] );
				if ( response[2] ) {
					infoBox.children('.ipsRedirect_loading').hide();
					infoBox.children('.ipsRedirect_progress').show().find('.ipsProgressBar_progress').css({ width: ( response[2] + '%' ) });
				}
				
				var newurl = baseurl + '&mr=' + JSON.stringify( response[0] );
				
				if ( response[0] == 'JTIyX19kb25lJTIy' ) {
					window.location = newurl;	
				} else {
					redirectStep( infoBox, newurl, baseurl );
				}
			},
			error: function( jqXHR, textStatus )
			{
				if( jqXHR.responseText )
				{
					infoBox.children('span').html( jqXHR.responseText );
					return;
				}

				if ( jqXHR === 'timeout' )
				{
					var counter = ( getUrlParam('count') === false ) ? 0 : parseInt( getUrlParam('count') );
				
					if ( counter < 101 )
					{
						window.location = window.location.replace( /&count=([0-9]+?)(&|$)/, '' ) + '&count=' + ( counter + 1 );
						return;
					}
					
					if ( confirm( "The server encountered multiple instances where it has stopped responding.\nPress 'OK' to reload this page and re-run this installation step.") )
					{
						window.location = window.location.replace( /&count=([0-9]+?)(&|$)/, '' ) + '&count=' + ( counter + 1 );
						return;
					}
				}

				window.location = url;
			}
		});
	};
	
	var getUrlParam = function( name )
	{
		if ( name = ( new RegExp( '[?&]'+encodeURIComponent( name ) + '=([^&]*)' ) ).exec( location.search ) )
		{
			return decodeURIComponent( name[1] );
		}
		
		return false;
  	};
	
	$('.ipsMultipleRedirect').each(function(){
		redirectStep( $(this).children('.no_js_hide'), $(this).attr('data-url') + '&mr=MA==', $(this).attr('data-url') );
	});
})