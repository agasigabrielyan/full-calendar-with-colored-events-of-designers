BX.ready(function() {
    $(function() {
        $('#calendar').fullCalendar({
          schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
          timeFormat: 'h:mm',
          locale: 'ru',
          resourceAreaWidth: "15%",
          displayEventTime : false,
          monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
            monthNamesShort: ['Янв.','Фев.','Март','Апр.','Май','Июнь','Июль','Авг.','Сент.','Окт.','Ноя.','Дек.'],
            dayNames: ["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
            dayNamesShort: ["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
            buttonText: {
                prev: "<",
                next: ">",
                prevYear: "<<",
                nextYear: ">>",
                today: "Сегодня",
                month: "Месяц",
                week: "Неделя",
                day: "День"
           },
          editable: false, // enable draggable events
          droppable: false, // this allows things to be dropped onto the calendar
          aspectRatio: 2,
          scrollTime: '00:00', // undo default 6am scrollTime
          header: {
            left: 'today prev,next',
            center: 'title',
            right: 'timelineDay,timelineWeek,timelineMonth'
          },
          defaultView: 'timelineDay',
          views: {
            timelineDay: {
                type: 'timeline',
            },
            timelineWeek: {
              type: 'timeline',
              duration: { days: 7 }
            },
            timelineMonth: {
                type: 'timeline',
                duration: { days: 30 },
                slotWidth: 150,
            }
          },
          resourceLabelText: 'Менеджеры',
          resources: BX.message('RESOURCES'),
          events: BX.message('EVENTS'),
          drop: function(date, jsEvent, ui, resourceId) {
              /*debugger;*/
          },
          eventReceive: function(event) {
               /*debugger;*/
          },
          eventDrop: function(event) {

          },
          dayClick: function(date, jsEvent, view, resourceObj) {
                debugger;
          },
          dayRender: function(day, cell) {
                cell.css({backgroundColor: '#fff'});
          },
          eventRender: function (event, element, monthView) {
            if(event.extendedProps) {
                if(event.extendedProps.className !== "calendar-cell__absence-day") {
                    let newDescription = "<div href='" + event.extendedProps.url + "' class='event-cell'>";
                            if(event.extendedProps.leadStatusName) {
                                newDescription += '<div class="event-cell__status"><b>' + event.extendedProps.leadStatusName + '</b></div>';
                            } else {
                                newDescription += '<div class="event-cell__status"><b> Сделка без лида</b></div>';
                            }
                            newDescription += "<div class='event-cell__title'>" + event.extendedProps.title + "</div>";
                            newDescription += "<div>Нач: " + event.extendedProps.start + "</div>";
                            newDescription += "<div>Ок: " + event.extendedProps.end + "</div>";
                        newDescription += "</div>"
                    element.append(newDescription);
                }
            }

          },
          viewRender: function(info) {


              let fcCellTextTime = $(".fc-cell-text-time");
              let timeCoordination = [];
                  timeCoordination['12am'] = '00';
                  timeCoordination['1am']  = '01';
                  timeCoordination['2am']  = '02';
                  timeCoordination['3am']  = '03';
                  timeCoordination['4am']  = '04';
                  timeCoordination['5am']  = '05';
                  timeCoordination['6am']  = '06';
                  timeCoordination['7am']  = '07';
                  timeCoordination['8am']  = '08';
                  timeCoordination['9am']  = '09';
                  timeCoordination['10am'] = '10';
                  timeCoordination['11am'] = '11';

                  timeCoordination['12pm'] = '12';
                  timeCoordination['1pm']  = '13';
                  timeCoordination['2pm']  = '14';
                  timeCoordination['3pm']  = '15';
                  timeCoordination['4pm']  = '16';
                  timeCoordination['5pm']  = '17';
                  timeCoordination['6pm']  = '18';
                  timeCoordination['7pm']  = '19';
                  timeCoordination['8pm']  = '20';
                  timeCoordination['9pm']  = '21';
                  timeCoordination['10pm'] = '22';
                  timeCoordination['11pm'] = '23';

              for(let i=0; i<fcCellTextTime.length; i++) {
                let currentText = $(fcCellTextTime[i]).text();
                $(fcCellTextTime[i]).text(timeCoordination[currentText]);
              }

              // Задать выходные дни
              let timelineType = info.name;

          },
        });
     });
});

