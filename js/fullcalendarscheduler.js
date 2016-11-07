$(document).ready(function() {
	
	var fullcalendarscheduler_now = new Date();
	var fullcalendarscheduler_date = ('0' + fullcalendarscheduler_now.getDate()).slice(-2);
	var fullcalendarscheduler_month = ('0' + (fullcalendarscheduler_now.getMonth() + 1)).slice(-2);
	var fullcalendarscheduler_today = fullcalendarscheduler_now.getFullYear()+'-'+fullcalendarscheduler_month+'-'+fullcalendarscheduler_date;
	
	/* Debut Calendar du menu de Gauche */
	$('#fullcalendar_scheduler_mini').fullCalendar({
		header: {
			schedulerLicenseKey: '0233290846-fcs-1478511282',
			left: 'title',
			center: 'prev,today,next',
			right: ''
		},
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'month',
		editable: false,
		aspectRatio: 0.5,
		dayClick: function(date, jsEvent, view, resourceObj) {
			console.log('dayClick then call ajax to reload big calendar', date.format(), resourceObj);
		}
		
	});
	/* Fin Calendar du menu de Gauche */
	
	console.log(fullcalendar_scheduler_resources_allowed);
	
	/* Début Calendar centrale */
	$('#fullcalendar_scheduler').fullCalendar({
		header: {
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			left: 'title',
			center: 'prev,today,next',
			right: ''
		},
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'agendaDay',
		editable: true,
		selectable: true,
		aspectRatio: 1.8,
		
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

		resources: fullcalendar_scheduler_resources_allowed,
		events: fullcalendar_scheduler_events_by_resource,
		/*[
			{ id: '1', resourceId: '1', start: '2016-11-06', end: '2016-11-08', title: 'event 1' },
			{ id: '2', resourceId: '2', start: '2016-11-07T09:00:00', end: '2016-11-07T14:00:00', title: 'event 2' },
			{ id: '3', resourceId: '3', start: '2016-11-07T12:00:00', end: '2016-11-08T06:00:00', title: 'event 3' },
			{ id: '4', resourceId: '4', start: '2016-11-07T07:30:00', end: '2016-11-07T09:30:00', title: 'event 4' },
			{ id: '5', resourceId: '4', start: '2016-11-07T10:00:00', end: '2016-11-07T15:00:00', title: 'event 5' }
		],*/

		select: function(start, end, jsEvent, view, resource) {
			console.log(
				'select',
				start.format(),
				end.format(),
				resource ? resource.id : '(no resource)'
			);
		},
		dayClick: function(date, jsEvent, view, resource) {
			console.log(
				'dayClick',
				date.format(),
				resource ? resource.id : '(no resource)'
			);
		}
		
		
		
	});
	/* Fin Calendar centrale */
	
});