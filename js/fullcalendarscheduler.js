$(document).ready(function() {
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
			schedulerLicenseKey: '0233290846-fcs-1478511282',
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
		aspectRatio: 1.8,
		eventOverlap: false,
		defaultTimedEventDuration: '01:00:00',
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

		select: function(start, end, jsEvent, view, resource) {
			console.log('select called: ', start.format(), resource ? 'ID res = '+resource.id : '(no resource)');
			
			var date = start.format('YYYY-MM-DD');
			
			var hour_start = start.format('HH');
			var minute_start = start.format('mm');
			
			start.add(1, 'hour');
			
			var hour_end = start.format('HH');
			var minute_end = start.format('mm');
			
			// show form
			//$('#form_add_event').show();
			fullcalendarscheduler_div.dialog({
				modal: true
				,width: 'auto'
				,title: fullcalendarscheduler_title_dialog_create_event
				,buttons: [
					{
						text: fullcalendarscheduler_button_dialog_add
						,icons: { primary: "ui-icon-check" }
						,click: function() {
							/**
							 * Ajax call to create event with json return to add the new event into calendar 
							 */
							// si valide 
								// create event
								// add event to calendar
								// close form
							// si non valide
								// champ en erreur en retour
							// si annuler
								// close form
							$( this ).dialog( "close" );
						}
					},
					{
						text: fullcalendarscheduler_button_dialog_cancel
						,icons: { primary: "ui-icon-close" }
						,click: function() {
							$( this ).dialog( "close" );
						}
					}
				]
				,open: function( event, ui ) {
					$('#date_start').val(date);
					dpChangeDay('date_start', fullcalendarscheduler_date_format);
					$('#date_end').val(date);
					dpChangeDay('date_end', fullcalendarscheduler_date_format);
					
					$('#date_starthour').val(hour_start);
					$('#date_startmin').val(minute_start);
					
					$('#date_endhour').val(hour_end);
					$('#date_endmin').val(minute_end);
					
					fullcalendarscheduler_div.find('#fk_resource').val(resource.id).trigger('change');
				}
			});
			
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
				,
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
						//,allDay: +event.allDay // event.allDay vos "true" ou "false" et le "+" de devant est là pour convertir en int
						//,resourceId: event.resourceId
						//,fk_element_resource: event.fk_element_resource // il s'agit du rowid de la table "element_resources"
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
		}
	});
	/* Fin Calendar centrale */
	
});