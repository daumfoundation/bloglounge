/*
 * SimpleModal 1.1.1 - jQuery Plugin
 * http://www.ericmmartin.com/projects/simplemodal/
 * http://plugins.jquery.com/project/SimpleModal
 * http://code.google.com/p/simplemodal/
 *
 * Copyright (c) 2007 Eric Martin - http://ericmmartin.com
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Revision: $Id: jquery.simplemodal.js 93 2008-01-15 16:14:20Z emartin24 $
 *
 */
/* ncloud add */
/* move center */
/* overlayClickClose */

(function ($) {
	/*
	 * Stand-alone function to create a modal dialog.
	 * 
	 * @param {string, object} data A string, jQuery object or DOM object
	 * @param {object} [options] An optional object containing options overrides
	 */
	$.modal = function (data, options) {
		return $.modal.impl.init(data, options);
	};

	/*
	 * Stand-alone close function to close the modal dialog
	 */
	$.modal.close = function () {
		// call close with the external parameter set to true
		$.modal.impl.close(true);
	};

	$.modal.center = function(x, y) {
		$.modal.impl.center(x, y);
	};

	$.modal.collectHeight = function() {
		$.modal.impl.collectHeight();
	};

	$.modal.move = function(obj,x,y) {
		$.modal.impl.move(obj,x,y);
	};

	/*
	 * Chained function to create a modal dialog.
	 * 
	 * @param {object} [options] An optional object containing options overrides
	 */
	$.fn.modal = function (options) {
		//$(window).scroll( function() {
		//		$.modal.impl.close(true);
		//});

		return $.modal.impl.init(this, options);
	};

	$.modal.make = function(arg1, arg2, arg3) {
		
	}

	/*
	 * SimpleModal default options
	 * 
	 * overlay: (Number:50) The overlay div opacity value, from 0 - 100
	 * overlayId: (String:'modalOverlay') The DOM element id for the overlay div
	 * overlayCss: (Object:{}) The CSS styling for the overlay div
	 * containerId: (String:'modalContainer') The DOM element id for the container div
	 * containerCss: (Object:{}) The CSS styling for the container div
	 * close: (Boolean:true) Show the default window close icon? Uses CSS class modalCloseImg
	 * closeTitle: (String:'Close') The title value of the default close link. Depends on close
	 * closeClass: (String:'modalClose') The CSS class used to bind to the close event
	 * persist: (Boolean:false) Persist the data across modal calls? Only used for existing
	            DOM elements. If true, the data will be maintained across modal calls, if false,
				the data will be reverted to its original state.
	 * onOpen: (Function:null) The callback function used in place of SimpleModal's open
	 * onShow: (Function:null) The callback function used after the modal dialog has opened
	 * onClose: (Function:null) The callback function used in place of SimpleModal's close
	 */
	$.modal.defaults = {
		overlay: 10,
		overlayId: 'modalOverlay',
		overlayCss: {},
		overlayClickClose: true,
		containerId: 'modalContainer',
		containerCss: {},
		close: true,
		closeTitle: 'Close',
		closeClass: 'modalClose',
		persist: false,
		onOpen: null,
		onShow: null,
		onClose: null,
		onMove: null
	};

	/*
	 * Main modal object
	 */
	$.modal.impl = {
		/*
		 * Modal dialog options
		 */
		opts: null,
		/*
		 * Contains the modal dialog elements and is the object passed 
		 * back to the callback (onOpen, onShow, onClose) functions
		 */
		dialog: {},
		/*
		 * Initialize the modal dialog
		 */
		init: function (data, options) {
			// don't allow multiple calls
			if (this.dialog.data) {
				return false;
			}

			// merge defaults and user options
			this.opts = $.extend({}, $.modal.defaults, options);

			// determine how to handle the data based on its type
			if (typeof data == 'object') {
				// convert DOM object to a jQuery object
				data = data instanceof jQuery ? data : $(data);

				// if the object came from the DOM, keep track of its parent
				if (data.parent().parent().size() > 0) {
					this.dialog.parentNode = data.parent();

					// persist changes? if not, make a clone of the element
					if (!this.opts.persist) {
						this.dialog.original = data.clone(true);
					}
				}
			}
			else if (typeof data == 'string' || typeof data == 'number') {
				// just insert the data as innerHTML
				data = $('<div>').html(data);
			}
			else {
				// unsupported data type!
				if (console) {
					console.log('SimpleModal Error: Unsupported data type: ' + typeof data);
				}
				return false;
			}
			this.dialog.data = data.addClass('modalData');
			data = null;

			// create the modal overlay, container and, if necessary, iframe
			this.create();

			// display the modal dialog
			this.open();

			// useful for adding events/manipulating data in the modal dialog
			if ($.isFunction(this.opts.onShow)) {
				this.opts.onShow.apply(this, [this.dialog]);
			}

			// don't break the chain =)
			return this;
		},
		/* move center */
		center: function(x, y) {
			var container = this.dialog.container.get(0);
			if(container) {
				var c_width = $(container).width();	
				var c_height = $(container).height();

				if(this.dialog.data.length > 0) {	
					if(c_height==0) {
						c_height = this.dialog.data.height();
					}
					if(c_width==0) {
						c_width = this.dialog.data.width();
					}
				}

				if(x == undefined) x = 0;
				if(y == undefined) y = 0;
				if($.browser.msie && $.browser.version < 7) {
					container.style.top = ($(window).scrollTop() + ($(window).height() - c_height)/2) + y + "px";				
					container.style.left = ($(window).scrollLeft() + ($(window).width() - c_width)/2) + x + "px";
				} else {
					container.style.top = (($(window).height() - c_height)/2) + y + "px";				
					container.style.left = (($(window).width() - c_width)/2) + x + "px";
				}
			}
		},
		/* move obj */
		move: function(obj,x,y) {
			var container = this.dialog.container.get(0);
			if(container) {
				container = $(container);
				obj = $(obj);
				if(typeof x == "undefined") x = "left:-10";
				if(typeof y == "undefined") y = "bottom:10";

				var pos = obj.position();
				var posx = 0;
				var posy = 0;

				var xs = x.split(":");
				if(xs.length == 1) xs[1] = "0";

				var ys = y.split(":");
				if(ys.length == 1) ys[1] = "0";

				if(xs[1] == "center") {
					xs[1] = -(container.width() / 2);
				}

				if(ys[1] == "center") {
					ys[1] = -(container.height() / 2);
				}
				if ($.browser.msie && ($.browser.version < 7)) {
					switch(xs[0]) {
						case "right":
							posx = (pos.left + obj.width() - container.width() + parseInt(xs[1])) + "px";
						break;
						case "center":
							posx = (pos.left + (obj.width()/2) + parseInt(xs[1])) + "px";
						break;
						default:
							posx = (pos.left + parseInt(xs[1])) + "px";
						break;
					}
					switch(ys[0]) {
						case "top":
							posy = (pos.top - container.height() + parseInt(ys[1])) + "px";
						break;
						case "center":
							posy = (pos.top + (obj.height()/2) + parseInt(ys[1])) + "px";
						break;
						default:
							posy = (pos.top + obj.height() + parseInt(ys[1])) + "px";
						break;
					}
				} else {
					switch(xs[0]) {
						case "right":
							posx = (pos.left + obj.width() - container.width() + parseInt(xs[1])-$(window).scrollLeft()) + "px";
						break;
						case "center":
							posx = (pos.left + (obj.width()/2) + parseInt(xs[1])-$(window).scrollLeft()) + "px";
						break;
						default:
							posx = (pos.left + parseInt(xs[1])-$(window).scrollLeft()) + "px";
						break;
					}
					switch(ys[0]) {
						case "top":
							posy = (pos.top - container.height() + parseInt(ys[1])-$(window).scrollTop()) + "px";
						break;
						case "center":
							posy = (pos.top + (obj.height()/2) + parseInt(ys[1])-$(window).scrollTop()) + "px";
						break;
						default:
							posy = (pos.top + obj.height() + parseInt(ys[1])-$(window).scrollTop()) + "px";
						break;
					}
				}

				container.css("left" , posx);
				container.css("top" ,  posy);

				if($.isFunction(this.opts.onMove)) {
					this.opts.onMove(container, obj, xs[0], xs[1], ys[0], ys[1]);
				}

				return true;
			}
			return false;
		},
		/*
		 * Create and add the modal overlay and container to the page
		 */
		create: function () {
			// create the overlay
			this.dialog.overlay = $('<div>')
				.attr('id', this.opts.overlayId)
				.addClass('modalOverlay')
				.css($.extend(this.opts.overlayCss, {
					opacity: this.opts.overlay / 100,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					zIndex: 3000
				}))
				.hide()
				.appendTo('body');

			if( this.opts.overlayClickClose ) {
				var thisobj = this;
				this.dialog.overlay.click( function() {
					thisobj.close();
				})
			}

			// create the container
			this.dialog.container = $('<div>')
				.attr('id', this.opts.containerId)
				.addClass('modalContainer')
				.css($.extend(this.opts.containerCss, {
					position: 'fixed',
					zIndex: 3100
				}))
				.append(this.opts.close 
					? '<a class="modalCloseImg ' 
						+ this.opts.closeClass 
						+ '" title="' 
						+ this.opts.closeTitle + '"></a>'
					: '')
				.hide()
				.appendTo('body');

			// fix issues with IE and create an iframe
			if ($.browser.msie && ($.browser.version < 7)) {
				this.fixIE();
			}

			// hide the data and add it to the container
			this.dialog.container.append(this.dialog.data.hide());
		},
		/*
		 * Bind events
		 */
		bindEvents: function () {
			var modal = this;

			// bind the close event to any element with the closeClass class
			$('.' + this.opts.closeClass).click(function (e) {
				e.preventDefault();
				modal.close();
			});
		},
		/*
		 * Unbind events
		 */
		unbindEvents: function () {
			// remove the close event
			$('.' + this.opts.closeClass).unbind('click');
		},
		/*
		 * Fix issues in IE 6
		 */
		fixIE: function () {
			var wHeight = $(window).height() + 'px';
			var wWidth = $(window).width() + 'px';

			// position hacks
			this.dialog.overlay.css({position: 'absolute', height: wHeight, width: wWidth});
			this.dialog.container.css({position: 'absolute'});

			// add an iframe to prevent select options from bleeding through
			this.dialog.iframe = $('<iframe src="javascript:false;">')
				.css($.extend(this.opts.iframeCss, {
					opacity: 0, 
					position: 'absolute',
					height: wHeight,
					width: wWidth,
					zIndex: 1000,
					width: '100%',
					top: 0,
					left: 0
				}))
				.hide()
				.appendTo('body');
		},
		/*
		 * Open the modal dialog elements
		 * - Note: If you use the onOpen callback, you must "show" the 
		 *         overlay and container elements manually 
		 *         (the iframe will be handled by SimpleModal)
		 */
		open: function () {
			// display the iframe
			if (this.dialog.iframe) {
				this.dialog.iframe.show();
			}

			if ($.isFunction(this.opts.onOpen)) {
				// execute the onOpen callback 
				this.opts.onOpen.apply(this, [this.dialog]);
			}
			else {
				// display the remaining elements
				this.dialog.overlay.show();
				this.dialog.container.show();
				this.dialog.data.show();
			}

			// bind default events
			this.bindEvents();
		},
		/*
		 * Close the modal dialog
		 * - Note: If you use an onClose callback, you must remove the 
		 *         overlay, container and iframe elements manually
		 *
		 * @param {boolean} external Indicates whether the call to this
		 *     function was internal or external. If it was external, the
		 *     onClose callback will be ignored
		 */
		close: function (external) {
			// prevent close when dialog does not exist
			if (!this.dialog.data) {
				return false;
			}

			if ($.isFunction(this.opts.onClose) && !external) {
				// execute the onClose callback
				this.opts.onClose.apply(this, [this.dialog]);
			}
			else {
				// if the data came from the DOM, put it back
				if (this.dialog.parentNode) {
					// save changes to the data?
					if (this.opts.persist) {
						// insert the (possibly) modified data back into the DOM
						this.dialog.data.hide().appendTo(this.dialog.parentNode);
					}
					else {
						// remove the current and insert the original, 
						// unmodified data back into the DOM
						this.dialog.data.remove();
						this.dialog.original.appendTo(this.dialog.parentNode);
					}
				}
				else {
					// otherwise, remove it
					this.dialog.data.remove();
				}

				// remove the remaining elements
				this.dialog.container.remove();
				this.dialog.overlay.remove();
				if (this.dialog.iframe) {
					this.dialog.iframe.remove();
				}

				// reset the dialog object
				this.dialog = {};
			}

			// remove the default events
			this.unbindEvents();
		}
	};
})(jQuery);

// modal plus function

function fnModalCenter() {
	$.modal.center(0,0);
}

function showModal(modalName, options) {
	var modal = $( modalName );
	if(typeof(options) == "undefined") {
		options = [];
	}

	modal.modal({containerId : modal.attr("id") + "Container", onShow : options.onShow, onClose : options.onClose, onOpen : options.onOpen, onMove : options.onMove });

	if(typeof options.baseObject !=  "undefined") {
		if(typeof options.position == "undefined") {
			var pos = ['left:0','bottom:0'];
		} else {
			var pos = options.position.split(",");
		}
		
		$.modal.move(options.baseObject, pos[0], pos[1]);
	}

}

function hideModal() {
	$.modal.close();
}


function makeModal(baseModal, data, options) {
	if(data.substr(0,1) == "#") {
		$(baseModal + " .modalInput").html($(data).html());
	} else {
		$(baseModal + " .modalInput").html(data);
	}
	showModal(baseModal, options);
}