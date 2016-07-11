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
			born: "born",
			empty: "empty",
			error: "error",
			inputHighlight: "bounce animated",
			loading: "loading",
			reserved: "reserved"
		},
		_urls: {
			ajax: "../../../app/core/ajax/"
			//ajax: "/zkusebna-kobylisy/app/core/ajax/"
		},
		_dateFormats: {
			//dateTime: 'DD.MM.YYYY H:mm',
			dateTime: 'd.m.Y H:i',
			//date: 'DD.MM.YYYY',
			date: 'd.m.Y',
			//unixDateTime: 'd.m.Y H:i',
			unixDateTime: 'DD.MM.YYYY HH:mm',
			//unixDate: 'd.m.Y',
			unixDate: 'DD.MM.YYYY'
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

			$("select").each(function() {
				var $elm = $(this);

				$elm.addClass(Zkusebna._classes.born);
				$elm.one('change', function() {
					$elm.removeClass(Zkusebna._classes.born);
				});
			});

		},
		_copyToClipboard: function(text) {
			window.prompt("Pro zkopírování zmáčkni postupně: Ctrl+C, Enter", text);
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
				addPurpose: $("#add-purpose"),
				editPurpose: $("#edit-purpose"),
				approved: $("#approved-reservations"),
				unapproved: $("#unapproved-reservations"),
				repeated: $("#repeated-reservations"),
				items: $("#items")
			};

			this._renderDashboard();
			this._approveHandler();
			this._deleteHandler();
			this._itemsHandler();
			this._addPurposeHandler();
			this._reservationsHandler();

		},

		_addPurposeHandler: function() {

			var self = this;

			this.$wrappers.addPurpose.on('submit', function(e) {

				e.preventDefault();
				Zkusebna._request("admin.php", self.$wrappers.addPurpose.serialize() + "&action=addPurpose", function(data) {
					if (data.result) {
						alert("Uloženo!");
						self.$wrappers.addPurpose.find("input").val("");
					}
					else {
						alert('Došlo k chybě. Zkontrolujte, zda máte vyplněná pole, nebo zda již účel rezervace existuje');
					}
				});

			});

		},
		_approveHandler: function() {
			var self = this;

			this.$wrappers.admin.on("click", ".approve", function() {
				var data = {
					action: "approve",
					reservationId: $(this).parents("[data-id]").attr("data-id")
				};

				Zkusebna._request("admin.php", data, self._repaintDashboard.bind(self),
				null,
				$(this));
			});

		},
		_deleteHandler: function() {
			var self = this;

			this.$wrappers.admin.on("click", ".delete, .delete-item", function() {
				var action = $(this).hasClass("delete") ? "delete" : "deleteItem",
					data = {
						action: action,
						reservationId: $(this).parents("[data-id]").attr("data-id")
					};

				if (action == "deleteItem") data.itemId = $(this).attr("data-item");

				Zkusebna._request("admin.php", data, self._repaintDashboard.bind(self),
					null,
					$(this));
			});
		},
		_itemsHandler: function() {
			this.$wrappers.admin.on("click", ".editable", function() {

				if (typeof this.isActive == "undefined") {
					this.isActive = true;
				}
				if (this.isActive == false) {
					this.isActive = true;
				}

				var $node = $(this),
					node_val = $(this).text(),
					node_width = $(this).width(),
					table = $(this).attr("data-table"),
					item_id = $(this).attr("data-id"),
					input_id = "temp_editable_item",
					column = $(this).attr("data-column"),
					post_data = {
						action: "updateItem",
						table: table,
						itemId: item_id,
						column: column
					},
					self = this,
					saveValue = function() {
						post_data.val = $("#" + input_id).val();
						Zkusebna._request("admin.php", post_data, function(res) {
							if (res.result == "failure") {
								$node.find("input").addClass(Zkusebna._classes.error + " " + Zkusebna._classes.inputHighlight);

								setTimeout(function(){
									$("#" + input_id).removeClass(Zkusebna._classes.inputHighlight);
								}, 1000);
							}
							else {
								self.isActive = false;
								$node.html(post_data.val).removeClass(Zkusebna._classes.active);
							}
						});
					};

				if (this.isActive) {
					$(this).addClass(Zkusebna._classes.active);
					$(this).html("<input type='text' value='" + node_val + "' id='" + input_id + "' style='width: " + (node_width + 20) + "px;'><span class='icon-checkmark save'></span><span class='icon-close close'></span>");
				}

				$(this).find(".close").on("click", function(){
					self.isActive = false;
					$node.removeClass(Zkusebna._classes.active).html(node_val);
				});
				$(this).find(".save").on("click", saveValue);
				$("#" + input_id).select().focus().on('blur', saveValue);
			});

			this.$wrappers.admin.on("click", ".deletable", function() {
				var $node = $(this),
					table = $(this).attr("data-table"),
					item_id = $(this).attr("data-id"),
					item_parent_selector = $(this).attr("data-parent"),
					post_data = {
						action: "deleteIt",
						table: table,
						itemId: item_id
					};

				if (confirm("Jste si jisti?")) {
					Zkusebna._request("admin.php", post_data, function(data) {
						if (data.result == "failure") {
							alert(data.message);
						}
						else {
							$node.parents(item_parent_selector).fadeOut({
								duration: 500,
								easing: 'linear',
								complete: function() {
									$(this).remove();
								}
							});
						}
					});
				}
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
				self.$wrappers.items.html(data.items);
				self.$wrappers.editPurpose.html(data.purpose);
				self._repaintDashboard(data);

				self.$wrappers.admin.find(".icon-mobile, .icon-mail").on('click', function() {
					Zkusebna._copyToClipboard($(this).attr('data-message'));
				});

			});

		},
		_repaintDashboard: function(data) {
			this.$wrappers.approved.html(data.approved);
			this.$wrappers.unapproved.html(data.unapproved);
			this.$wrappers.repeated.html(data.repeated);
			this.$wrappers.admin.find(".archive").on('click', function(e) {
				e.preventDefault();
				$(this).parent().parent().addClass("show-archived");
			});
			Zkusebna._qtips();
			Zkusebna._expandableHandler();
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
					events: function(start, end, timezone, callback) {
						Zkusebna._request(
							"calendar-events.php",
							{ date_from: start.format(), date_to: end.format() },
							function(items) {
								var events = items.reduce(function(stack, item) {
									if (!stack[item['reservationID']]) {
										stack[item['reservationID']] = {
											id: item['reservationID'],
											start: item['start'],
											end: item['end'],
											title: item['name'],
											categories: {},
											items: []
										};
									}
									stack[item['reservationID']]['items'].push(item);
									stack[item['reservationID']]['categories'][item['category']] = true;

									return stack
								}, []);

								var reservations = [];

								for (var id in events) {
									if (!events.hasOwnProperty(id)) continue;
									reservations.push(events[id]);
								}

								callback(reservations);
							},
							null,
							self.$calendar
						);
					},
					eventClick: function(event) {
						if (event.title) {
							var message;
							message = event.image ? '<img src="' + event.image + '" class="eventImage"/>' : '';
							message += 'Rezervoval/a: <strong>' + event.title + '</strong><br>';
							message += 'Rezervace: <strong>' + event.start.format('D.M. HH:mm') + '</strong> - <strong>' + event.end.format('D.M. HH:mm') + '</strong>';
							message += '<h2>Rezervované položky:</h2>';
							message += '<ul>';
							event.items.forEach(function(item) {
								message += '<li class="' + item['category'] + '">' + item.itemName + '</li>';
							});
							message += '</ul>';
							Zkusebna._popup(event.title, message, "event-preview");
						}
					},
					eventRender: function(event, $element) {
						var categories = Object.keys(event.categories),
							categoriesLength = categories.length;

						$element.addClass("cats-" + categoriesLength + " " + Object.keys(event.categories).join(" "));
						$element.find('.fc-content').prepend(function() {
							var output = "<em>";
							for (var i = 0; i < categoriesLength; i++) {
								output += '<i class="' + categories[i] + '"></i>';
							}
							output += "</em>";
							return output;
						});
						$element.find('.fc-time').html("<small>" + event.start.format('HH:mm') + "-" + event.end.format('HH:mm') + "</small>");

						var message = '<strong class="block"><span class="block mbs">' + event.title + '</span>' + event.start.format('D.M. HH:mm') + ' - ' + event.end.format('D.M. HH:mm') + '</strong>';

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
					timeFormat: 'H(:mm)'
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
				email: $('#email'),
				purpose: $("#purpose"),
				repeat_from: $('#repeat_from')//,
				//repeat_to: $('#repeat_to')
			};
			this.reservableItems = {};
			this.reservedItems = [];
			this.activeReservation = false;

			this._cart();
			this._datetimePickers();
			this._purposeSelect();
			this._renderItems();

			this.$wrappers.reservedItemsWrapper.mCustomScrollbar({
				scrollInertia: 80
			});

		},
		deleteItem: function(item_id) {

			this.deleteItems([item_id]);

		},
		deleteItems: function(item_ids) {

			this.reservedItems = this.reservedItems.diff(item_ids);

			if (!this.reservableItems.length) {
				this.activeReservation = false;
			}

			for (var i = 0; i < item_ids.length; i++) {
				this.$wrappers.items.find("[data-id=" + item_ids[i] +"]").removeClass(Zkusebna._classes.reserved);
			}

			this._renderReservedItems();

		},
		_purposeSelect: function() {
			this.$formInputs.purpose.on('change', this._renderItems.bind(this));
		},
		reserve: function(item_id, $item) {

			if (this._validateForm()) {
				this.activeReservation = true;
				this.reservedItems.push(item_id);
				$item.addClass(Zkusebna._classes.reserved);
				this._renderReservedItems();
			}

		},
		updateReservationDate: function($input) {
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
							$.magnificPopup.close();
							if ($(e.target).hasClass("button")) {
								self.deleteItems(non_compatible_ids);
							}
							else {
								$input.focus();
							}
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
			var self = this;

			if (!this.reservedItems.length) {
				this.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
				setTimeout(function() {
					if (!self.activeReservation) {
						self.$wrappers.reservedItems.html("");
					}
				}, 700);
				return;
			}
			else {
				this.$wrappers.reservedItemsWrapper.removeClass(Zkusebna._classes.empty);
			}

			var output = "<ul>";

			for (var i = 0; i < this.reservedItems.length; i++) {
				output += "<li class='item' class='reservable-item-" + this.reservedItems[i] + "' data-id='" + this.reservedItems[i] + "'>" + this.reservableItems[this.reservedItems[i]].itemName + "</li>"
			}

			output += "</ul>";
			output += "<div class='price'>" + this._getReservedItemsPrice() + "</div>";
			output += "<div class='finish'><span class='button--red'>Zrušit</span><span class='button--white'>Odeslat</span></div>";

			this.$wrappers.reservedItems.html(output);
		},
		_getReservedItemsPrice: function() {
			var price = 0;
			for (var i = 0; i < this.reservedItems.length; i++) {
				price += Math.round(parseInt(this.reservableItems[this.reservedItems[i]].price) * (100 - parseInt(this.reservableItems[this.reservedItems[i]].discount)) / 100);
			}
			return price;
		},
		_createReservation: function() {

			var ids = [],
				self = this;

			for (var i = 0; i < this.reservedItems.length; i++) {
				ids.push("item_ids[]=" + this.reservedItems[i]);
			}

			if (!this._validateForm() || !this._validateDates(this.$formInputs.date_from)) return false;

			Zkusebna._request("create-reservation.php", this.$form.serialize() + "&" + ids.join("&"),
				function(data) {
					if (data.result == "collision") {
						self.deleteItems(data.collisions);
						Zkusebna._popup(data.heading, data.message, "event-preview");
					}
					else {
						Zkusebna._popup(data.heading, data.message);
						self.$wrappers.reservedItemsWrapper.addClass(Zkusebna._classes.empty);
						self.$wrappers.reservedItems.html('');
						self.reservedItems = [];
						self.activeReservation = false;
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

			Zkusebna._request("items-view.php", this.$form.serialize(), function(data) {

				self.reservableItems = data.items;

				self.$wrappers.items.html(data.html);

				Zkusebna._expandableHandler();
				self._reservableHandler();
				self._renderReservedItems();

			});

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
				dateFormat = Zkusebna._dateFormats.date,
				dateTimeFormat = Zkusebna._dateFormats.dateTime,
				pickerOptions = {
					format: dateTimeFormat,
					formatDate: dateTimeFormat,
					dayOfWeekStart: 1,
					step: 60,
					minDate: new Date(),
					startDate: new Date(),
					roundTime: 'ceil',
					onChangeDateTime: function(dp, $input) {
						var date1 = $input.val(),
							date2 = $($input.attr('data-connected-to')).val();

						if (!date1 || !date2) {
							return;
						}
						if (!self._validateDates($input)) {
							$input.focus();
							return;
						}

						if (self.reservedItems.length) {
							self.updateReservationDate($input);
						}
						self._renderItems();
					}
				};
			$(".datetimepicker:not([data-type])").datetimepicker(pickerOptions);

			var datePickerOptions = $.extend(true, {}, pickerOptions);
			datePickerOptions.format = dateFormat;
			datePickerOptions.formatDate = dateFormat;
			datePickerOptions.timepicker = false;
			$(".datetimepicker[data-type='date']").datetimepicker(datePickerOptions);

		},
		_validateDates: function($inputs) {
			var date_from,
				date_to,
				is_valid = true;

			$inputs.each(function() {
				var dateFormat = ($(this).attr("data-type")=="date") ? Zkusebna._dateFormats.unixDate : Zkusebna._dateFormats.unixDateTime;
				if ($(this).attr("data-date-type")=="from") {
					date_from = $(this).val();
					date_to = $($(this).attr("data-connected-to")).val();
				}
				else {
					date_from = $($(this).attr("data-connected-to")).val();
					date_to = $(this).val();
				}
				if (!date_from || !date_to || date_from == date_to || moment(date_from, dateFormat) >= moment(date_to, dateFormat)) {
					is_valid = false;
				}
			});

			return is_valid;
		},
		_validateForm: function() {

			var isValid = {
					name: /^.{2,}$/g.test(this.$formInputs.name.val()),
					phone: /^(\+420 *)?([0-9]{3} *){3}$/g.test(this.$formInputs.phone.val()),
					email: /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(this.$formInputs.email.val()),
					purpose: !!this.$formInputs.purpose.val()
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

