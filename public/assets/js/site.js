Array.prototype.diff = function(a) {
	return this.filter(function(i) {return a.indexOf(i) < 0;});
};
Date.parseDate = function( input, format ){
	return moment(input,format).toDate();
};
Date.prototype.dateFormat = function( format ){
	return moment(this).format(format);
};
$.datetimepicker.setLocale('cs');



(function($, window, document){


	var Zkusebna;

	Zkusebna = {

		_classes: {
			active: "active",
			alreadyReserved: "already-reserved",
			empty: "empty",
			error: "error",
			inputHighlight: "bounce animated",
			loading: "loading",
			reserved: "reserved"
		},
		_urls: {
			ajax: "../../../app/core/ajax/"
		},

		init: function() {

			if ($("#homepage").length) {
				this.homepage.init();
			}
			if ($("#reserve").length) {
				this.reserve.init();
			}
			if ($("#admin").length) {
				this.admin.init();
			}

		},
		_expandableHandler: function() {

			var self = this;

			$(".expandable").each(function() {

				if (!$(this).hasClass(self._classes.active)) {
					$("ul", $(this).parent()).removeClass(self._classes.active);
				}
				else {
					$("+ ul", this).addClass(self._classes.active);
				}

				$(this).click(function() {

					if ($(this).hasClass(self._classes.active)) {
						$(this).removeClass(self._classes.active);
						$("." + self._classes.active, $(this).parent()).removeClass(self._classes.active);
					}
					else {
						$(this).addClass(self._classes.active);
						$("+ ul", this).addClass(self._classes.active);
					}

				});

			});

		},
		_popup: function(heading, message, wrapperClass) {
			var src;
			if (wrapperClass !== undefined) {
				src = '<div class="' + wrapperClass + '"><h2>' + heading + '</h2><p>' + message + '</p></div>';
			}
			else {
				src = '<h2>' + heading + '</h2><p>' + message + '</p>';
			}

			$.magnificPopup.open({
				items: {
					src: src,
					type: 'inline'
				}
			});
		},
		_qtips: function() {
			$('.tooltip').qtip({
				content: {
					attr: 'data-message'
				},
				style: {
					background: 'black',
					color: '#FFFFFF'
				},
				position: {
					at: 'top center',
					my: 'bottom center'
				}
			});
		},
		_request: function(file, data, success, fail, $item, dataType) {

			var self = this;

			if ($item && $item.length) {
				$item.addClass(this._classes.loading);
			}

			$.ajax({
				method: "POST",
				url: this._urls.ajax + file,
				dataType: dataType || 'json',
				data: data,
				complete: function() {
					if ($item && $item.length) {
						$item.removeClass(self._classes.loading);
					}
				},
				success: success || null,
				error: fail || null
			});

		}

	};
	Zkusebna.admin = {

		init: function() {

			this.$wrappers = {
				admin: $("#admin"),
				approved: $("#approved-reservations"),
				unapproved: $("#unapproved-reservations"),
				items: $("#items")
			};

			this._renderDashboard();
			this._approveHandler();
			this._itemsHandler();
			this._reservationsHandler();

		},

		_approveHandler: function() {
			var self = this;

			this.$wrappers.admin.on("click", "i", function() {
				var action = $(this).hasClass("approve") ? "approve" : "delete";

				Zkusebna._request("admin.php", { action: action, reservationId: $(this).parent().attr("data-id") }, function(data) {
					self.$wrappers.approved.html(data.approved);
					self.$wrappers.unapproved.html(data.unapproved);
					Zkusebna._qtips();
					Zkusebna._expandableHandler();
				});
			});

		},
		_itemsHandler: function() {
			this.$wrappers.items.on("click", ".editable", function() {

				var $node = $(this),
					val = $(this).text(),
					item_id = $(this).attr("data-id"),
					input_id = "temp_editable_item",
					column = $(this).attr("data-column"),
					data = {
						action: "updateItem",
						itemId: item_id,
						column: column
					},
					self = this;

				$(this).toggleClass(Zkusebna._classes.active);

				if ($(this).hasClass(Zkusebna._classes.active)) {
					$(this).html("<input type='text' value='" + val + "' id='" + input_id + "'>");
				}
				$("#" + input_id).select().focus().on('blur', function() {
					data.val = $(this).val();
					Zkusebna._request("admin.php", data, function(res) {
						if (res.result == "failure") {
							$node.find("input").addClass(Zkusebna._classes.error + " " + Zkusebna._classes.inputHighlight);

							setTimeout(function(){
								$("#" + input_id).removeClass(Zkusebna._classes.inputHighlight);
							}, 1000);
						}
						else {
							$node.removeClass(Zkusebna._classes.active).html(data.val);
						}
					});
				});
			});
		},
		_reservationsHandler: function() {
			this.$wrappers.admin.on("click", ".expandable", function() {
				$(this).parent().toggleClass("expanded");
			});
		},
		_renderDashboard: function() {

			var self = this;

			Zkusebna._request("admin.php", {}, function(data) {
				self.$wrappers.approved.html(data.approved);
				self.$wrappers.unapproved.html(data.unapproved);
				self.$wrappers.items.html(data.items);
				Zkusebna._qtips();
				Zkusebna._expandableHandler();
			});

		}

	};
	Zkusebna.homepage = {

		init: function() {

			this.calendar.init();

		},
		calendar: {

			init: function() {
				this.$calendar = $('#calendar');

				var self = this;

				this.$calendar.fullCalendar({
					eventLimit: true,
					header: {
						left: 'prev,next today',
						center: 'title',
						right: 'month,agendaWeek,agendaDay'
					},
					lang: "cs",
					eventClick: function(event) {
						if (event.title) {
							var message = (event.image ? '<img src="' + event.image + '" class="eventImage"/>' : '') + 'Rezervoval/a: <strong>' + event.name + '</strong> Rezervace: <strong>' + event.start.format('D.M. HH:mm') + '</strong> - <strong>' + event.end.format('D.M. HH:mm') + '</strong>';
							Zkusebna._popup(event.title, message, "event-preview");
						}
					},
					eventRender: function(event, $element) {

						$element.find('.fc-time').html("<small>" + event.start.format('HH:mm') + "-" + event.end.format('HH:mm') + "</small>");

						var message = '<strong class="block"><span class="block mbs">' + event.title + '</span>' + event.name + '<br>' + event.start.format('D.M. HH:mm') + ' - ' + event.end.format('D.M. HH:mm') + '</strong>';

						$element.qtip({
							content: message,
							style: {
								background: 'black',
								color: '#FFFFFF'
							},
							position: {
								at: 'top center',
								my: 'bottom center'
							}
						});
					},
					viewRender: function(view) {
						var date_from = view.start.format(),
							date_to = view.end.format();

						Zkusebna._request(
							"calendar-events.php",
							{ date_from: date_from, date_to: date_to },
							function(events) {
								self._renderCalendarEvents(events);
							},
							null,
							self.$calendar
						);

					},
					timeFormat: 'H(:mm)'
				});

			},
			_renderCalendarEvents: function(events) {

				var self = this;

				events.forEach(function(evt) {

					self.$calendar.fullCalendar( 'renderEvent', {
						img: evt.image,
						className: evt.category,
						title: evt.name,
						start: evt.date_from,
						end: evt.date_to,
						name: evt.reserved_by
					});

				});
			}

		}

	};
	Zkusebna.reserve = {

		init: function() {

			this.$wrappers = {
				items: $('#items-wrapper'),
				reservedItems: $('#reserved-items'),
				reservedItemsWrapper: $('#reserved-items-wrapper')
			};
			this.$form = $('#form__reserve');
			this.$formInputs = {
				date_from: $('#date_from'),
				date_to: $('#date_to'),
				name: $('#name'),
				phone: $('#phone'),
				email: $('#email')
			};
			this.reservableItems = {};
			this.reservedItems = [];

			this._cart();
			this._datetimePickers();
			this._renderItems();

			this.$wrappers.reservedItemsWrapper.mCustomScrollbar({
				scrollInertia: 80
			});

		},
		deleteItem: function(item_id) {

			this.deleteItems([item_id]);

		},
		deleteItems: function(item_ids) {

			var $items = $("");

			for (var i = 0; i < item_ids.length; i++) {
				$items = $items.add($(".reservable-item-" + item_ids[i]));
			}

			this.reservedItems = this.reservedItems.diff(item_ids);
			this.$wrappers.reservedItems.html(this._renderReservedItems());

			if ($items && $items.length) {
				$items.removeClass(Zkusebna._classes.reserved);
			}

		},
		reserve: function(item_id, $item) {

			if (this._validateForm()) {
				this.reservedItems.push(item_id);
				$item.addClass(Zkusebna._classes.reserved);

				this.$wrappers.reservedItems.html(this._renderReservedItems());
			}

		},
		updateReservationDate: function() {
			//checks for collisions within reserved items
			var self = this;

			Zkusebna._request("get-reserved-items.php", this.$form.serialize(),function(data) {

					var non_compatible_names = [],
						non_compatible_ids = [],
						compatible_ids = [];

					data.forEach(function(elm) {
						if (self.reservedItems.indexOf(elm.id) > -1) {
							non_compatible_names.push(self.reservableItems[elm.id].name);
							non_compatible_ids.push(elm.id);
						}
					});

					compatible_ids = self.reservedItems.diff(non_compatible_ids);

					if (non_compatible_names.length) {
						$.magnificPopup.open({
							items: {
								src: '<div class="reservation-warning"><h2>Změnou data přijdete o následující položky:</h2>' + non_compatible_names.join("<br>") + '<ul class="tac table cols-2"><li><span class="button--red">Zrušit</span></li><li><span class="button">Potvrdit</span></li></ul></div>',
								type: 'inline'
							},
							modal: true
						});
						$(".reservation-warning").on("click", "span", function(e) {
							if ($(e.target).hasClass("button")) {
								self.deleteItems(non_compatible_ids);
							}
							else {
								//TODO: uzivatel musi vybrat jine datum!
							}
							$.magnificPopup.close();
						});
					}

					self._renderItems();

				},
				null,
				this.$wrappers.items
			);
		},
		_cancelReservation: function() {

			this.deleteItems(this.reservedItems);

		},
		_cart: function() {
			var self = this;

			this.$wrappers.reservedItems.on("click", "li.item", function(){
				self.deleteItem($(this).attr("data-id"));
			});
			this.$wrappers.reservedItems.on("click", ".button--white", this._createReservation.bind(this));
			this.$wrappers.reservedItems.on("click", ".button--red", this._cancelReservation.bind(this));

		},
		/**
		 * renders cart with reserved items
		 * @returns {string}
		 * @private
		 */
		_renderReservedItems: function() {
			if (!this.reservedItems.length) {
				this.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
				return "";
			}
			else {
				this.$wrappers.reservedItemsWrapper.removeClass(Zkusebna._classes.empty);
			}

			var output = "<ul>",
				price = 0;

			for (var i = 0; i < this.reservedItems.length; i++) {
				output += "<li class='item' class='reservable-item-" + this.reservedItems[i] + "' data-id='" + this.reservedItems[i] + "'>" + this.reservableItems[this.reservedItems[i]].name + "</li>"
				price += parseInt(this.reservableItems[this.reservedItems[i]].price);
			}

			output += "</ul>";
			output += "<div class='price'>" + price + "</div>";
			output += "<div class='finish'><span class='button--red'>Zrušit</span><span class='button--white'>Potvrdit</span></div>";

			return output;
		},
		_createReservation: function() {

			var ids = [],
				self = this;

			for (var i = 0; i < this.reservedItems.length; i++) {
				ids.push("item_ids[]=" + this.reservedItems[i]);
			}

			if (!this._validateForm()) return false;

			Zkusebna._request("create-reservation.php", this.$form.serialize() + "&" + ids.join("&"),
				function(data) {
					if (data.result == "collision") {
						self.deleteItems(data.collisions);
					}
					else {
						Zkusebna._popup(data.heading, data.message);
						self.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
						self.$wrappers.reservedItems.html('');
						self.reservedItems = [];
						self._renderItems();
					}
				}
			);
		},
		/**
		 * Renders HTML tree of items for current user. Dates are not necessary
		 * @param date_from string - date format DD.MM.YYYY HH:MM
		 * @param date_to string
		 * @param email string
		 * @private
		 */
		_renderItems: function() {
			var self = this;

			Zkusebna._request("items-view.php", this.$form.serialize(), renderCallback);

			function renderCallback(data) {

				self.reservableItems = data.items;

				self.$wrappers.items.html(data.html);

				Zkusebna._expandableHandler();
				self._reservableHandler();
			}

		},
		_reservableHandler: function() {

			var self = this;

			$("ul.item-list .reservable").each(function() {

				if (self.reservedItems.indexOf($(this).attr("data-id")) > -1) {
					$(this).addClass(Zkusebna._classes.reserved);
				}

				$(this).click(function(e) {

					e.stopPropagation();

					if ($(this).hasClass(Zkusebna._classes.alreadyReserved)) {
						Zkusebna._popup("Toto už je rezervované", "<strong>" + $(this).attr("data-name") + "</strong> má rezervaci od " + $(this).attr("data-date-from") + " do " + $(this).attr("data-date-to"));
					}
					else if ($(this).hasClass(Zkusebna._classes.reserved)) {
						self.deleteItem($(this).attr("data-id"));
					}
					else {
						self.reserve($(this).attr("data-id"), $(this));
					}

				});

			});

		},
		_datetimePickers: function() {

			var self = this,
				datetimeFormat = 'd.m.Y H:i',
				pickerOptions = {
					format: datetimeFormat,
					dayOfWeekStart: 1,
					step: 60,
					minDate: new Date(),
					startDate: new Date(),
					roundTime: 'ceil',
					//mask:'31.12.9999 23:59',
					onChangeDateTime: function() {
						var date_from = self.$formInputs.date_from.val(),
							date_to = self.$formInputs.date_to.val();

						if (date_from && date_to) {
							if (self.reservedItems.length) {
								self.updateReservationDate();
							}
							self._renderItems();
						}
					}
				};

			pickerOptions.onShow = function(){
				//this.setOptions({
				//	maxDate: moment(self.$formInputs.date_to.val(), datetimeFormat).isValid() ? self.$formInputs.date_to.val() : false
				//});
			};
			this.$formInputs.date_from.datetimepicker(pickerOptions);


			pickerOptions.onShow = function(){
				//this.setOptions({
				//	minDate: moment(self.$formInputs.date_from.val(), datetimeFormat).isValid() ? self.$formInputs.date_from.val() : false
				//});
			};
			this.$formInputs.date_to.datetimepicker(pickerOptions);

		},
		_validateForm: function() {

			var isValid = {
					name: /^.{2,}$/g.test(this.$formInputs.name.val()),
					phone: /^(\+420 *)?([0-9]{3} *){3}$/g.test(this.$formInputs.phone.val()),
					email: /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(this.$formInputs.email.val())
				},
				formIsValid = true,
				self = this;

			for (var input_id in isValid) {
				if (isValid.hasOwnProperty(input_id)) {
					var $input = $("#" + input_id);

					if (isValid[input_id]) {
						$input.removeClass("error");
					}
					else {
						$input.addClass("error bounce animated");
						formIsValid = false;

						setTimeout(function(){
							self.$form.find(".bounce.animated").removeClass("bounce animated");
						}, 1000);
					}
				}
			}

			if (!formIsValid) {
				$("html, body").stop().animate({ scrollTop: 0 }, '500', 'swing');
			}

			return formIsValid;
		}

	};



	Zkusebna.init();


	//return {
	//	delete: Zkusebna.reserve.delete.bind(Zkusebna.reserve)
	//}
})(jQuery, window, document);

