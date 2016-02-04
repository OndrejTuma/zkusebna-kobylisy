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



//var ZK_APP = (function($, window, document){


	var Zkusebna;

	Zkusebna = {

		_classes: {
			active: "active",
			alreadyReserved: "already-reserved",
			empty: "empty",
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
				reservations: $("#reservations")
			};

			this._printReservations();

		},

		_printReservations: function() {

			var self = this;

			$.ajax({
				method: "POST",
				url: Zkusebna._urls.ajax + "admin-reservations.php",
				success: function(data) {
					self.$wrappers.reservations.html(data);
				}
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
				this.categoryColor = {
					zkusebna: '#b8860b',
					technika: '#67a712',
					nastroje: '#800080'
				};

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
							$.magnificPopup.open({
								items: {
									src: '<div class="event-preview"><h2>' + event.title + '</h2> Rezervoval/a: <strong>' + event.name + '</strong> Rezervace: <i>' + event.start.format('D.M. HH:mm') + ' - ' + event.end.format('D.M. HH:mm') + '</i></div>',
									type: 'inline'
								}
							});
						}
					},
					viewRender: function(view) {
						var date_from = view.start.format(),
							date_to = view.end.format();

						Zkusebna._request(
							"calendar-events.php",
							{ date_from: date_from, date_to: date_to },
							function(events) {
								self._renderCalendarEvents(events);
							}
						);

					}
				});

			},
			_getCategoryColor: function(category_name) {
				return this.categoryColor[category_name] ? this.categoryColor[category_name] : null;
			},
			_renderCalendarEvents: function(events) {

				var self = this;

				events.forEach(function(elm, i) {

					self.$calendar.fullCalendar( 'renderEvent', {
						title: events[i].name,
						start: events[i].date_from,
						end: events[i].date_to,
						name: events[i].reserved_by,
						color: self._getCategoryColor(events[i].category)
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
			this.reservedItems = {};
			this.hasActiveReservation = false;

			this._cart();
			this._datetimePickers();
			this._renderItems();

			window.onbeforeunload = this._leave.bind(this);

			this.$wrappers.reservedItemsWrapper.mCustomScrollbar({
				scrollInertia: 80
			});

		},
		deleteItem: function(item_id) {

			this.deleteItems([item_id]);

		},
		deleteItems: function(item_ids) {

			var self = this,
				ids = [],
				$items = $("");

			for (var i = 0; i < item_ids.length; i++) {
				ids.push("item_ids[]=" + item_ids[i]);
				$items = $items.add($("[data-id='" + item_ids[i] + "']"));
			}

			Zkusebna._request("items-delete-reservations.php", this.$form.serialize() + "&" + ids.join("&"),
				function(data) {
					self._deleteSuccess(data, $items);
				},
				function(xhr, error) {
					alert(error);
				},
				$items
			);
		},
		updateItems: function(item_ids) {

			var self = this,
				ids = [],
				$items = $("");

			for (var i = 0; i < item_ids.length; i++) {
				ids.push("item_ids[]=" + item_ids[i]);
				$items = $items.add($("[data-id='" + item_ids[i] + "']"));
			}

			Zkusebna._request("items-update-reservations.php", this.$form.serialize() + "&" + ids.join("&"),
				function(data) {
					//TODO - it works, no need to notify of succesfull update
				},
				null,
				$items
			);
		},
		reserve: function(item_id) {

			var self = this,
				$item = $("[data-id='" + item_id + "']");

			Zkusebna._request("items-add-reservation.php", this.$form.serialize() + "&item_id=" + item_id,
				function(data) {
					self._reserveCallback($item, data);
				},
				function(xhr, error) {
					alert(error);
				},
				$item
			);

		},
		_cart: function() {
			var self = this;

			this.$wrappers.reservedItems.on("click", "li.item", function(){
				self.deleteItem($(this).attr("data-id"));
			});
			this.$wrappers.reservedItems.on("click", ".button--white", this._confirmReservation.bind(this));
			this.$wrappers.reservedItems.on("click", ".button--red", function(){
				self._cancelReservation();
			});

		},
		_leave: function() {
			if (this.hasActiveReservation || Object.keys(this.reservedItems).length) {
				this._cancelReservation();
				return 'Rezervace nebyla potvrzena a z bezpečnostních důvodů byla zrušena';
			}
		},
		_cancelReservation: function() {

			var $items = this.$wrappers.items.find("." + Zkusebna._classes.reserved),
				self = this;

			Zkusebna._request("items-cancel-reservation.php", this.$form.serialize(),
				function(data) {
					self._deleteSuccess(data, $items);
				},
				function(xhr, error) {
					alert(error);
				},
				$items
			);

		},
		updateReservationDate: function() {
			//checks for collisions within reserved items

			var self = this;

			Zkusebna._request("get-reserved-items.php", this.$form.serialize(),function(data) {

					var non_compatible_names = [],
						non_compatible_ids = [],
						compatible_ids = [];

					data.forEach(function(elm) {
						if (self.reservedItems[elm.id]) {
							non_compatible_names.push(self.reservedItems[elm.id]);
							non_compatible_ids.push(elm.id);
						}
					});

					compatible_ids = Object.keys(self.reservedItems).diff(non_compatible_ids);

					if (!non_compatible_names.length) {
						self.updateItems(compatible_ids);
						self._renderItems();
					}
					else {
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
								self.updateItems(compatible_ids);
								self._renderItems();
							}
							else {

							}
							$.magnificPopup.close();

						});
					}
				},
				null,
				this.$wrappers.items
			);
		},
		/**
		 * renders cart with reserved items
		 * @returns {string}
		 * @private
		 */
		_renderReservedItems: function() {
			if (!Object.keys(this.reservedItems).length) {
				this.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
				return "";
			}
			else {
				this.$wrappers.reservedItemsWrapper.removeClass(Zkusebna._classes.empty);
			}

			var output = "<ul>",
				price = 0;

			for (var i in this.reservedItems) {
				if (this.reservedItems.hasOwnProperty(i)) {
					output += "<li class='item' data-id='" + i + "'>" + this.reservedItems[i].name + "</li>"
					price += parseInt(this.reservedItems[i].price);
				}
			}

			output += "</ul>";
			output += "<div class='price'>" + price + "</div>";
			output += "<div class='finish'><span class='button--red'>Zrušit</span><span class='button--white'>Potvrdit</span></div>";

			return output;
		},
		_confirmReservation: function() {

			var ids = [],
				self = this;

			for (var i in this.reservedItems) {
				if (this.reservedItems.hasOwnProperty(i)) {
					ids.push("item_ids[]=" + i);
				}
			}

			Zkusebna._request("items-confirm-reservation.php", this.$form.serialize() + "&" + ids.join("&"),
				function(data) {
					$.magnificPopup.open({
						items: {
							src: '<h2>' + data.heading + '</h2><p>' + data.message + '</p>',
							type: 'inline'
						}
					});
					self.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
					self.hasActiveReservation = false;
					self.reservedItems = {};
					self.$wrappers.reservedItems.html('');
					self._renderItems();
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

			Zkusebna._request("items-view.php", this.$form.serialize(), function(data) {
					self.$wrappers.items.html(data);

					self._expandableHandler();
					self._reservableHandler();
				},
				null,
				null,
				"html"
			);

		},
		_deleteSuccess: function(data, $items) {

			if ($items && $items.length) {
				$items.removeClass(Zkusebna._classes.reserved);
			}

			if (data.result == "success") {
				for (var i = 0; i < data.items.length; i++) {
					delete this.reservedItems[data.items[i].id];
				}
				this.$wrappers.reservedItems.html(this._renderReservedItems());

			}
			else if (data.result == "failure") {
				$.magnificPopup.open({
					items: {
						src: '<h2 class="error">' + data.message + '</h2>',
						type: 'inline'
					}
				});
			}

		},
		_reserveCallback: function($item, data) {

			var form_error = false;
			for (var input_id in data.empties) {
				if (data.empties.hasOwnProperty(input_id)) {
					var $input = $("#" + input_id);

					if (data.empties[input_id]) {
						$input.addClass("error bounce animated");
						form_error = true;

						setTimeout(function(){
							for (var input_id in data.empties) {
								if (data.empties.hasOwnProperty(input_id) && data.empties[input_id]) {
									$("#" + input_id).removeClass("bounce animated");
								}
							}
						}, 1000);
					}
					else {
						$input.removeClass("error");
					}
				}
			}
			if (form_error) {
				$("html, body").stop().animate({ scrollTop: 0 }, '500', 'swing');
			}

			if (data.result == "success") {
				this.hasActiveReservation = true;
				this.reservedItems[data.item.id] = data.item;
				$item.addClass(Zkusebna._classes.reserved);

				this.$wrappers.reservedItems.html(this._renderReservedItems());
			}
			else if (data.result == "failure") {
				alert('Rezervaci se nepodařilo dokončit. Zkuste to prosím znovu a pokud problém přetrvá, kontaktujte administrátora stránek');
			}

		},
		_reservableHandler: function() {

			var self = this;

			$("ul#item-list .reservable").each(function() {

				$(this).click(function(e) {

					e.stopPropagation();

					if ($(this).hasClass(Zkusebna._classes.alreadyReserved)) {
						$.magnificPopup.open({
							items: {
								src: '<h2>Toto už je rezervované</h2><p><strong>' + $(this).attr('data-name') + '</strong> má rezervaci od ' + $(this).attr('data-date-from') + ' do ' + $(this).attr('data-date-to') + '</p>',
								type: 'inline'
							}
						});
					}
					else if ($(this).hasClass(Zkusebna._classes.reserved)) {
						self.deleteItem($(this).attr("data-id"))
					}
					else {
						self.reserve($(this).attr("data-id"));
					}

				});

			});

		},
		_expandableHandler: function() {

			$("ul#item-list .expandable").each(function() {

				if (!$(this).hasClass(Zkusebna._classes.active)) {
					$("ul", $(this).parent()).removeClass(Zkusebna._classes.active);
				}
				else {
					$("+ ul", this).addClass(Zkusebna._classes.active);
				}

				$(this).click(function(e) {

					e.stopPropagation();

					if ($(this).hasClass(Zkusebna._classes.active)) {
						$(this).removeClass(Zkusebna._classes.active);
						$("." + Zkusebna._classes.active, $(this).parent()).removeClass(Zkusebna._classes.active);
					}
					else {
						$(this).addClass(Zkusebna._classes.active);
						$("+ ul", this).addClass(Zkusebna._classes.active);
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
							if (self.hasActiveReservation) {
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

		}

	};



	Zkusebna.init();


	//return {
	//	delete: Zkusebna.reserve.delete.bind(Zkusebna.reserve)
	//}
//})(jQuery, window, document);

function fillMeUp() {
	for (var i = 1; i < 40; i++) {
		Zkusebna.reserve.reserve(i);
	}
}
//fillMeUp();

