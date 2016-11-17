$(document).ready(function() {
	var fullcalendarscheduler_mouseDown = false;
	document.body.onmousedown = function() { 
		fullcalendarscheduler_mouseDown = true;
	}
	document.body.onmouseup = function() {
		fullcalendarscheduler_mouseDown = false;
	}
	
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
		defaultTimedEventDuration: fullcalendarscheduler_defaultTimedEventDuration,
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

		select: function(start, end, jsEvent, view, resource) {
			console.log('select called: ', start.format(), resource ? 'ID res = '+resource.id : '(no resource)');
			
			// show form
			fullcalendarscheduler_div.dialog({
				modal: true
				,width: 'auto'
				,title: fullcalendarscheduler_title_dialog_create_event
				,buttons: [
					{
						text: fullcalendarscheduler_button_dialog_add
						,icons: { primary: "ui-icon-check" }
						,click: function() {
							
							self = this;
							
							$.ajax({
								url: fullcalendarscheduler_interface
								,dataType: 'json'
								,data: {
									json: 1
									,put: 'createEvent'
									,TParam: {
										type_code: $('#type_code').val()
										,label: $('#form_add_event input[name=label]').val()
										//allDay: +event.allDay // event.allDay vos "true" ou "false" et le "+" de devant est là pour convertir en int
										,date_start: $('#date_startyear').val()+'-'+$('#date_startmonth').val()+'-'+$('#date_startday').val()+' '+$('#date_starthour').val()+':'+$('#date_startmin').val()+':00'
										,date_end: $('#date_startyear').val()+'-'+$('#date_startmonth').val()+'-'+$('#date_startday').val()+' '+$('#date_starthour').val()+':'+$('#date_startmin').val()+':00'
										,note: $('#form_add_event textarea[name=note]').val()
										,fk_soc: $('#fk_soc').val()
										,contactid: $('#contactid').val()
										,fk_user: $('#fk_user').val()
										,fk_resource: $('#fk_resource').val()
									}
									,dateFrom: view.start.format('YYYY-MM-DD')
								}
								,
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
				,open: function( event, ui ) {
					// Format en majuscule pour l'objet moment() si non il renvoie le mauvais format
					var date = start.format(fullcalendarscheduler_date_format.toUpperCase());
					
					var hour_start = start.format('HH');
					var minute_start = start.format('mm');
					
					var duration = moment.duration(fullcalendarscheduler_defaultTimedEventDuration);
					start.add(duration);
					
					var hour_end = start.format('HH');
					var minute_end = start.format('mm');
					
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
			// TODO à finaliser avec un petit picto et l'action associée => reste encore à définir
			var link_a = '<a title="action 1" href="#">A</a>';
			var link_b = '<a title="action 2" href="#">B</a>';
			var link_c = '<a title="action 3" href="#">C</a>';
			element.find('.fc-content').append('<div class="ajaxtool">'+link_a+' '+link_b+' '+link_c+'</div>');
			
			element.find('.fc-content').append('<div class="link_thirdparty">'+event.link_company+'</div>');
			element.find('.fc-content').append('<div class="link_contact">'+event.link_contact+'</div>');
			
			element.find('.link_thirdparty a, .link_contact a').attr('title', '');
			element.find('.fc-content a').css('color', '#fff');
			
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
	
});