$(document).ready(function() {
	var fullcalendarscheduler_mouseDown = false;
	document.body.onmousedown = function() { 
		fullcalendarscheduler_mouseDown = true;
	};
	document.body.onmouseup = function() {
		fullcalendarscheduler_mouseDown = false;
	};
	
	var fullcalendarscheduler_now = new Date();
	var fullcalendarscheduler_date = ('0' + fullcalendarscheduler_now.getDate()).slice(-2);
	var fullcalendarscheduler_month = ('0' + (fullcalendarscheduler_now.getMonth() + 1)).slice(-2);
	var fullcalendarscheduler_today = fullcalendarscheduler_now.getFullYear()+'-'+fullcalendarscheduler_month+'-'+fullcalendarscheduler_date;
	
	var isLocaleFr = fullcalendarscheduler_initialLangCode == 'fr'; 
	
	
	/* Debut Calendar du menu de Gauche */
	$('#fullcalendar_scheduler_mini').fullCalendar({
		monthNames: (isLocaleFr ? ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"] : ''),
		//monthNamesShort: ["Janv", "Févr", "Mars", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Déc"],
	    //dayNames: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"],
	    dayNamesShort: (isLocaleFr ? ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"] : ''),
	    //dayNamesMin: ["Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa"],
		header: {
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			left: 'title',
			center: 'prev,today,next',
			right: ''
		},
		locale: fullcalendarscheduler_initialLangCode,
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'month',
		editable: false,
		aspectRatio: 0.5,
		dayClick: function(date, jsEvent, view, resourceObj) {
			console.log('dayClick event called and gotoDate is triggered to', date.format());
			$('#fullcalendar_scheduler').fullCalendar('gotoDate', date);
		},
		eventAfterAllRender: function( view ) {
			// Force enable "today" button
			$('#fullcalendar_scheduler_mini .fc-today-button').removeClass('fc-state-disabled');
			$('#fullcalendar_scheduler_mini .fc-today-button').prop('disabled', false);
		}
		
	});
	
	$('#fullcalendar_scheduler_mini .fc-today-button').click(function() {
		console.log('today button is triggered on mini calendar');
		$('#fullcalendar_scheduler').fullCalendar('today');
	});
	/* Fin Calendar du menu de Gauche */
	
	
	// TODO voir https://fullcalendar.io/docs/event_ui/ pour plus de détail sur les events
	
	/* Début Calendar centrale */
	$('#fullcalendar_scheduler').fullCalendar({
		monthNames: (isLocaleFr ? ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"] : ''),
		slotLabelFormat: (isLocaleFr ? "HH:mm" : ''),
		header: {
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			left: 'title',
			center: '',
			right: 'prev,today,next'
		},
		locale: fullcalendarscheduler_initialLangCode,
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'agendaDay',
		editable: true,
		selectable: true,
		selectHelper: true,
		aspectRatio: fullcalendarscheduler_aspectRatio,
		minTime: fullcalendarscheduler_minTime, // default 00:00
		maxTime: fullcalendarscheduler_maxTime, // default 23:00
		eventOverlap: false,
		snapDuration: fullcalendarscheduler_snapDuration,
		businessHours: [
			{ // Jours de semaines
				dow: [ 1, 2, 3, 4, 5 ], // Lundi, Mardi, Mercredi, Jeudi, Vendredi
				start: '08:00', // 8am
				end: '18:00' // 6pm
				//,constraint: 'available_hours' // TODO à voir comment marche la contrainte, actuellement tous les events peuvent être déplacés dans toutes les plages horaires => à voir si je restreint 
			},
			{ // Weekend
				dow: [ 0, 6 ], // Dimanche, Samedi
				start: '10:00', // 10am
				end: '16:00' // 4pm
			}
		],
		
		// TODO vérifier que c'est utile le "agendaTwoDay"
		views: {
			agendaTwoDay: {
				type: 'agenda',
				duration: { days: 2 },

				// views that are more than a day will NOT do this behavior by default
				// so, we need to explicitly enable it
				groupByResource: true

				//// uncomment this line to group by day FIRST with resources underneath
				//groupByDateAndResource: true
			}
		},

		//// uncomment this line to hide the all-day slot
		//allDaySlot: false,

		resources: fullcalendar_scheduler_resources_allowed, // Tableau d'objet
		//events: fullcalendar_scheduler_events_by_resource, // Tableau d'objet
		eventClick: function(event, jsEvent, view) {
			console.log('eventClick called: ', event, jsEvent);
			
			if (!$(jsEvent.target.parentElement).hasClass('ajaxtool_link') && !$(jsEvent.target.parentElement).hasClass('ajaxtool') && !$(jsEvent.target).hasClass('classfortooltip'))
			{
				// show form, seulement si le clic ne provient pas d'un lien "action rapide"
				showEventDialog(view, event.start, event.end, view.calendar.getResourceById(event.resourceId), event);	
			}
		},
		select: function(start, end, jsEvent, view, resource) {
			console.log('select called: ', start.format(), resource ? 'ID res = '+resource.id : '(no resource)');
			
			// show form
			showEventDialog(view, start, end, resource);
		},
		dayClick: function(date, jsEvent, view, resource) {
			console.log('dayClick called: ', date.format(), resource ? 'ID res = '+resource.id : '(no resource)');
		},
		/*eventDragStart: function(event, jsEvent, ui, view) {
			console.log('eventDragStart : ', event, jsEvent, ui, view);
		},*/
		/*eventDragStop: function(event, jsEvent, ui, view) {
			console.log('eventDragStop : ', event, jsEvent, ui, view);
		},*/
		eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
			console.log('eventDrop called and delta is: '+delta.toString(), event);
			
			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,put: 'updateTimeSlotAndResource' // update crénau horaire et ressource associée
					,event: {
						id: event.id
						,allDay: +event.allDay // event.allDay vos "true" ou "false" et le "+" de devant est là pour convertir en int
						,resourceId: event.resourceId
						,fk_element_resource: event.fk_element_resource // il s'agit du rowid de la table "element_resources"
						,start: event.start.format('YYYY-MM-DD HH:mm:ss')
						,end: event.end !== null && event.end !== "undefined" ? event.end.format('YYYY-MM-DD HH:mm:ss') : ''
						,deltaInSecond: delta.asSeconds()
					}
					,dateFrom: event.start.format('YYYY-MM-DD')
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
				revertFunc();
			}).done(function(response, textStatus, jqXHR) {
				console.log('Done: ', response);
			});
			
		},
		/*eventResizeStart: function(event, jsEvent, ui, view) {
			console.log('eventResizeStart : ', event, jsEvent, ui, view);
		},
		eventResizeStop: function(event, jsEvent, ui, view) {
			console.log('eventResizeStop : ', event, jsEvent, ui, view);
		},*/
		eventResize: function(event, delta, revertFunc, jsEvent, ui, view) {
			console.log('eventResize called and delta is: '+delta.toString(), event);
			//console.log(delta.asSeconds());
			
			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,put: 'updateTimeSlot' // update crénau horaire
					,event: {
						id: event.id
						,start: event.start.format('YYYY-MM-DD HH:mm:ss')
						,end: event.end.format('YYYY-MM-DD HH:mm:ss')
						,deltaInSecond: delta.asSeconds()
					}
					,dateFrom: event.start.format('YYYY-MM-DD')
				}
				,
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
				revertFunc();
			}).done(function(response, textStatus, jqXHR) {
				console.log('Done: ', response);
			});
			
		},
		
		viewRender: function( view, element ) {
			console.log('viewRender called: ', view, element);
			
			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,get: 'getEventsFromDate' // get all event from date
					,dateFrom: view.calendar.getDate().format('YYYY-MM-DD')
				}
				,
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
			}).done(function(response, textStatus, jqXHR) {
				console.log('viewRender Done: ', response);
				
				view.calendar.removeEvents();
				view.calendar.addEventSource(response.data.TEvent);
			});
		},
		eventRender: function(event, element, view) {
			if (event.id != null && event.id != 'undefined')
			{
				// TODO à finaliser avec un petit picto et l'action associée => reste encore à définir
				//console.log(event);
				var link_a = '<a title="action 1" class="ajaxtool_link" href="javascript:action_a('+event.id+');">A</a>';
				
				var action_detail = '<a class="ajaxtool_link need_to_be_adjust" href="'+fullcalendarscheduler_url_event_card+'?id='+event.id+'">'+fullcalendarscheduler_picto_detail+'</a>';
				var action_delete = '<a class="ajaxtool_link" href="javascript:delete_event('+event.id+');">'+fullcalendarscheduler_picto_delete+'</a>';
				
				//fullcalendarscheduler_picto_detail
				element.find('.fc-content').append('<div class="ajaxtool">'+link_a+' '+action_detail+' '+action_delete+'</div>');
				
				element.find('.fc-content')	.append('<div class="link_thirdparty">'+event.link_company+'</div>')
											.append('<div class="link_contact">'+event.link_contact+'</div>')
											.append('<div class="link_service">'+event.link_service+'</div>')
											.append('<div class="extrafields">'+event.showOptionals+'</div>');
				
				//element.find('.link_thirdparty a, .link_contact a, .link_service a').attr('title', '');
				element.find('.link_thirdparty a, .link_contact a, .link_service a').tipTip();
				
				element.find('.fc-content a').css('color', element.css('color'));
				
				if (typeof fullcalendarscheduler_TColorCivility[event.contact_civility] != 'undefined')
				{
					element.find('.fc-time, .fc-title').css('background', fullcalendarscheduler_TColorCivility[event.contact_civility]);
				}
			}
			
		},
		eventAfterAllRender: function (view) {
			// Pour un peu plus de confort pour éviter de bataillé avec l'adaptation de la hauteur du bloc sur le hover qui suit
			$('.fc-resizer').css('height', '12px');
			
			$('.fc-content').hover(function(jsEvent) {
				if (!fullcalendarscheduler_mouseDown)
				{
					var target = $(this).parent();
					var origin_height = parseInt(target.css('height'));
					target.data('originHeight', origin_height);
					
					if (origin_height < parseInt($(this).css('height'))) target.css('height', parseInt($(this).css('height'))+10);
				}
			}, function(jsEvent) {
				if (!fullcalendarscheduler_mouseDown)
				{
					var target = $(this).parent();
					target.css('height', target.data('originHeight'));
				}
			});
		}
	});
	/* Fin Calendar centrale */
	
	action_a = function(id)
	{
		alert('Update statut rapide de l\'event');
		var view = $('#fullcalendar_scheduler').fullCalendar('getView');
		console.log("view = >", view);
		
		var event = $("#fullcalendar_scheduler").fullCalendar( 'clientEvents', id );
		console.log("event => ", event);
		
		//view.calendar.removeEvents(id);
		//view.calendar.addEventSource(event);
	};
	
	action_b = function(id)
	{
		alert('Reste à faire');
	};
	
	delete_event = function(id)
	{
		var div = $('<div>').text(fullcalendarscheduler_content_dialog_delete);
		div.dialog({
			modal: true
			,width: 'auto'
			,title: fullcalendarscheduler_title_dialog_delete_event
			,buttons: [
				{
					text: fullcalendarscheduler_button_dialog_confirm
					,icons: { primary: "ui-icon-check" }
					,click: function() {
						
						self = this;
						
						$.ajax({
							url: fullcalendarscheduler_interface
							,dataType: 'json'
							,data: {
								json: 1
								,put: 'deleteEvent'
								,fk_actioncomm: id
							}
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
							$( self ).dialog( "close" );
						}).done(function(response, textStatus, jqXHR) {
							console.log('Done: ', response);

							if (response.TError.length > 0)
							{
								for (var x in response.TError)
								{
									$.jnotify(response.TError[x], 'error');
								}
							}
							else
							{
								var view = $('#fullcalendar_scheduler').fullCalendar('getView');
								view.calendar.removeEvents(id);
								
								$( self ).dialog( 'close' );
							}
							
						});
						
						
					}
				},
				{
					text: fullcalendarscheduler_button_dialog_cancel
					,icons: { primary: 'ui-icon-close' }
					,click: function() {
						$( this ).dialog( 'close' );
					}
				}
			]
		});
	};	
	
	
	
	
	showEventDialog = function(view, start, end, resource, event)
	{
		fullcalendarscheduler_div.dialog({
			modal: true
			,width: 'auto'
			,title: (typeof event !== 'undefined') ? fullcalendarscheduler_title_dialog_update_event : fullcalendarscheduler_title_dialog_create_event
			,buttons: [
				{
					text: (typeof event !== 'undefined') ? fullcalendarscheduler_button_dialog_update : fullcalendarscheduler_button_dialog_add
					,icons: { primary: "ui-icon-check" }
					,click: function() {
						
						self = this;
						
						var dataObject = $('#form_add_event').serializeObject();
						dataObject.json = 1;
						dataObject.put = 'createOrUpdateEvent';
						dataObject.fk_actioncomm = (typeof event !== 'undefined') ? event.id : 0;
						dataObject.dateFrom = view.start.format('YYYY-MM-DD');
						
						$.ajax({
							url: fullcalendarscheduler_interface
							,type: 'GET' // obligatoirement en GET car la méthode d'affichage des extrafields ne permet pas d'utiliser du POST à cause de la méthode showOptionals du commonObject
							,dataType: 'json'
							,data: dataObject
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
							$( self ).dialog( "close" );
						}).done(function(response, textStatus, jqXHR) {
							console.log('Done: ', response);

							if (response.TError.length > 0)
							{
								for (var x in response.TError)
								{
									$.jnotify(response.TError[x], 'error');
								}
							}
							else
							{
								view.calendar.removeEvents();
								view.calendar.addEventSource(response.data.TEvent);
								
								$( self ).dialog( 'close' );
							}
							
						});
						
					}
				},
				{
					text: fullcalendarscheduler_button_dialog_cancel
					,icons: { primary: 'ui-icon-close' }
					,click: function() {
						$( this ).dialog( 'close' );
					}
				}
			]
			,open: function( jsEvent, ui ) {
				// Init fields
				initEventFormFields(start, end, resource, event);
			}
		}).trigger('fullcalendarscheduler_trigger_show_event_dialog');
		
		
	};
	
	
	initEventFormFields = function(start, end, resource, event) {
		
		if (typeof event !== 'undefined')
		{
			fullcalendarscheduler_div.find('#type_code').val(event.type_code).trigger('change');
			fullcalendarscheduler_div.find('input[name=label]').val(event.title);
			fullcalendarscheduler_div.find('textarea[name=note]').val(event.desc);
			
			fullcalendarscheduler_div.find('#fk_soc').val(event.fk_soc).trigger('change');
			setTimeout( function() { // La modification du tiers charge en ajax le contenu du select contact, il faut donc update la selection de celui-ci après
				fullcalendarscheduler_div.find('#contactid').val(event.fk_socpeople).trigger('change');
			}, 150);
			
			fullcalendarscheduler_div.find('#fk_service').val(event.fk_service).trigger('change');
			fullcalendarscheduler_div.find('#search_fk_service').val(event.product_ref).trigger('change');
			
			if (typeof event.editOptionals != 'undefined')
			{
				fullcalendarscheduler_div.find('#extrafield_to_replace').replaceWith(event.editOptionals);
			}
			
		}
		
		// Format en majuscule pour l'objet moment() si non il renvoie le mauvais format
		var date = start.format(fullcalendarscheduler_date_format.toUpperCase());
		fullcalendarscheduler_div.find('#date_start').val(date);
		dpChangeDay('date_start', fullcalendarscheduler_date_format);
		fullcalendarscheduler_div.find('#date_end').val(date);
		dpChangeDay('date_end', fullcalendarscheduler_date_format);
		
		var hour_start = start.format('HH');
		var minute_start = start.format('mm');
		fullcalendarscheduler_div.find('#date_starthour').val(hour_start);
		fullcalendarscheduler_div.find('#date_startmin').val(minute_start);
		
		var hour_end = end.format('HH');
		var minute_end = end.format('mm');
		fullcalendarscheduler_div.find('#date_endhour').val(hour_end);
		fullcalendarscheduler_div.find('#date_endmin').val(minute_end);
		
		fullcalendarscheduler_div.find('#fk_resource').val(resource.id).trigger('change');
	};
	
});

$.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};
