/**
 * Slider Editor
 *
 * @copyright Commercial License By PavoThemes.Com 
 * @email pavothemes@gmail.com
 * @visit http://www.pavothemes.com
 */

(function( $ ) {
 
	$.fn.pavoUserPopup = function( initvar ) {
 
		/**
		 * Create List Layers By JSON Data.
		 */
		this.price = '';
		this.ischanged = false; 
		this.work = function(  ){
			return true; 
			var _this = this; 

			this.createSlider();
			$('input[name^=\'filter\']', this ).click( function() { 
				_this.makeRequest( _this );
				_this.createSelection( _this );
				_this.ischanged = true;
			} );

			this.createSelection();
			$('.clear-all').click( function(){ 
				$('input[name^=\'filter\']:checked', _this ).removeAttr('checked');
				$(".pav-slider .clear-price").click();
				_this.createSelection();
			} );
 		}, 

 		this.createSlider = function(){
 			var _this = this; 

 			$(".pav-slider", this ).each( function ( el ) {
 				$( ".pav-slider-el", el ).ionRangeSlider({
				    type: "double",
				    min: 0,
				    from:0,
				    to:12000,
				    max: 12000,
				    grid: true,
				    prefix: "$",
				    onFinish:function( data ){ 
				    	console.log( data );
				     	_this.price = (data.from_pretty + "," + data.to_pretty).replace(/\s+/,"");
				     	_this.makeRequest( _this );
				     	if( data.to != data.to_pretty || data.from != data.from_pretty ){
				     		$('.clear-price' , _this ).removeClass('hide').addClass('show');
				     	}else {
				     		$(this).removeClass('show').addClass('hide');
				     	}
				     	
				    }
				});

				$('.clear-price' , el ).click( function(){
	 				_this.price = "";
	 				$(this).removeClass('show').addClass('hide');
	 				_this.ischanged = true;
	 				_this.makeRequest( _this );
	 				var slider = $( ".pav-slider-el", el ).data("ionRangeSlider");
	 				slider.reset();
	 			} );

 			} );
 		},

 		this.createSelection = function( _this ){

 			var container = $(".filter-selection", this).find(".list-group-content");
 			container.empty(); 

 			if( $('input[name^=\'filter\']:checked', this ).length > 0 ){
 				$(".filter-selection", this).removeClass( "hide" ).addClass( "active" );
 			}else {
 				$(".filter-selection", this).removeClass( "active" ).addClass( "hide" );
 			}

 			$('input[name^=\'filter\']:checked', this ).each( function( element ){  
 				var _this = $(this); 
 				var item = $('<div class="list-group-item-choosed"><span>'
 					+$(this).data("filter-name")
 					+'</span><span class="remove"><i class="fa fa-times" aria-hidden="true"></i></span></div>');
 				item.click( function(){
 					_this.click();
 				} );
 				container.append( item );

 			} );
 		},

 		this.makeRequest = function( _this ){

 			filter = [];
			$('input[name^=\'filter\']:checked', this ).each(function(element) {
				filter.push(this.value);
			});

			var url 	 = $(this).data('action');
			var storeUrl = $(this).data( "store-action" );

			if( filter.length > 0 ){
				url += '&filter=' + filter.join(',');
				storeUrl += '&filter=' + filter.join(',');
			}
			if( this.price != "" ){
				url += "&price="+this.price; 
				storeUrl += "&price="+this.price; 
			}
			if( filter.length >0 || this.price != "" || this.ischanged ){
				$.ajax({
					url: url,
					dataType:'json',
					beforeSend:function(){
						$('.products-collection' ).append( $('<div class="ajax-preloading"><div class="preloading-inner"></div></div>') );
					},
					}).done( function( data ) {
						window.history.pushState( null, null, storeUrl );
						_this.replaceContent( data );
						
				}	);
			}
 		},

 		this.replaceContent = function( data ){
 			$('.products-collection' ).html( data['products'] );
 			$('.products-collection #grid-view').click();
 			$('.products-collection .ajax-preloading' ).remove();
 		}

		//THIS IS VERY IMPORTANT TO KEEP AT THE END
		return this;
	};
 	
 	$(document).ready( function(){
 		$('.pavuserpopup').pavoUserPopup( ''  ).work();
 	} );
})( jQuery );
/***/