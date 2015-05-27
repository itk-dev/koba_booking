/**
 * @file
 * Contains booking-calendar directive.
 */

/**
 * Booking calendar directive.
 *
 * @TODO: Move kobaFactory into own service file?
 */
angular.module("kobaApp")
  .factory("kobaFactory", ['$http', '$q',
    function ($http, $q) {
      'use strict';

      var factory = {};

      /**
       * Get available resources from Koba.
       *
       * @returns array
       *   Promise to resolve with the data or reject on failure.
       *   Array of resources.
       */
      factory.getResources = function getResources() {
        var defer = $q.defer();

        $http({
          url: '/booking/api/resources',
          method: 'GET',
          headers: {
            "Content-Type": 'text/html',
            "Accept": 'text/html'
          }
        }).success(function(response) {
          defer.resolve(response);
        }).error(function(error) {
          defer.reject(error);
        });

        return defer.promise;
      };

      /**
       * Get bookings from Koba, for a given resource, and time interval.
       *
       * @param resource
       *   The resource to get free/busy time for.
       * @param from
       *   The unix timestamp for start of interest period.
       * @param to
       *   The unix timestamp for end of interest period.
       *
       * @returns array
       *   Array of free busy times for the given resource.
       *   Promise to resolve with the data or reject on failure.
       */
      factory.getBookings = function getBookings(resource, from, to) {
        var defer = $q.defer();

        $http({
          url: '/booking/api/bookings?res=' + resource + '&from=' + from + '&to=' + to,
          method: 'GET',
          headers: {
            "Cache-Control": 'no-cache, no-store, must-revalidate',
            "Content-Type": 'text/html',
            "Accept": 'text/html'
          }
        }).success(function(response) {
          defer.resolve(response);
        }).error(function(error) {
          defer.reject(error);
        });

        return defer.promise;
      };

      return factory;
    }
  ])
  .directive("bookingCalendar", ['kobaFactory', '$window',
    function (kobaFactory, $window) {
      'use strict';

      return {
        restrict: 'E',
        scope: {
          "selectedDate": "=",
          "selectedResource": "=",
          "selectedStart": "=",
          "selectedEnd": "=",
          "bookings": "=",
          "interestPeriod": "=",
          "disabled": "="
        },
        link: function (scope) {
          var bookings = [];

          // Used for
          scope.loaded = false;

          // Get selected booking timestamps.
          var bookingTime = getSelecteDateTimesAsUnixTimestamp();

          // Watch for changes to date and time selections.
          scope.$watchGroup(['selectedStart', 'selectedEnd', 'selectedDate'],
            function (val) {
              if (!val) {
                return;
              }

              // Update selected booking time.
              bookingTime = getSelecteDateTimesAsUnixTimestamp();
            }
          );

          /**
           * Is the time interval selected?
           *   returns 'first', 'middle', 'last', 'first-last'
           *     corresponding to whether it is the first, middle, last, first-last (both first and last) of a booking.
           *     used for which class to attach to the time interval.
           *
           *     @TODO: It dose only return 'first' and TRUE/FALSE?
           *
           * @param timeInterval
           *   The time interval to evaluate.
           * @returns boolean|string
           *   The result of the evaluation (@TODO: WHICH CAN BE??).
           */
          scope.selected = function(timeInterval) {
            var selectedTime = parseInt(timeInterval.timeMoment.format('x'));

            if (bookingTime['from'] <= selectedTime && bookingTime['to'] > selectedTime) {
              if (bookingTime['from'] === selectedTime) {
                return 'first';
              }
              else {
                return true;
              }
            }
            else {
              return false;
            }
          };

          /**
           * Extend the current time interval or move to another time interval.
           *
           * Select a time interval and 1 hour forward.
           *
           * @param timeInterval
           *   The clicked time interval.
           */
          scope.select = function (timeInterval) {
            /**
             * Why ?
             */
            if (timeInterval.disabled || bookingTime['from'] === timeInterval.timeMoment) {
              return;
            }

            /**
             * @TODO: Explain the math behind this ?
             */
            scope.selectedStart = new Date(
              timeInterval.timeFromZero.hours * 60 * 60 * 1000 + timeInterval.timeFromZero.minutes * 60 * 1000
            );

            scope.selectedEnd = new Date(scope.selectedStart.getTime() + (bookingTime['to'] - bookingTime['from']));
          };

          /**
           * Render the calendar.
           *
           * Generates the calendar timeIntervals.
           * - based on
           *   - interest period
           *   - disabled intervals
           *   - booked intervals.
           *
           * Also sets a text to display in the calendar.
           */
          function renderCalendar() {
            // Reset time intervals.
            scope.timeIntervals = [];

            // Calculate number of time intervals.
            // Interest period is in hours, so just multiply difference between start and end by two.
            var numberOfIntervals = (scope.interestPeriod.end - scope.interestPeriod.start) * 2;

            // Render calendar.
            for (var i = 0; i < numberOfIntervals; i++) {
              var time = moment(scope.selectedDate).add(i * 30, 'minutes').add(scope.interestPeriod.start, 'hours');

              // See if the time interval is disabled.
              var disabled = false;
              for (var j = 0; j < scope.disabled.length; j++) {
                if (
                  time >= moment(scope.selectedDate).add(scope.disabled[j][0], 'hours') &&
                  time < moment(scope.selectedDate).add(scope.disabled[j][1], 'hours')
                ) {
                  disabled = true;
                  break;
                }
              }

              // Get the Date representation of time.
              var timeDate = time.toDate();

              // See if the time interval is free.
              var free = true;
              for (j = 0; j < scope.bookings.length; j++) {
                if (timeDate.getTime() >= scope.bookings[j].start * 1000 &&
                  timeDate.getTime() < scope.bookings[j].end * 1000) {
                  free = false;
                  break;
                }
              }

              // Add the time interval.
              scope.timeIntervals.push({
                'timeFromZero': {
                  'hours': scope.interestPeriod.start + parseInt(i / 2),
                  'minutes': (i % 2) * 30
                },
                'time': timeDate,
                'timeMoment': time,
                'halfhour': (time.minutes() > 0),
                'disabled': disabled,
                'booked': !free
              });
            }
          }

          /**
           * Get current date/time selections as unix timestamps.
           *
           * Build timestamps to send to the server based on the date picker and time picker selector. The first issue is
           * that the time picker returns date information for the time selected today, while we want to combine the date
           * select and only get the time selected (without the date from the time picker).
           *
           * @returns {{from: number, to: number}}
           */
          function getSelecteDateTimesAsUnixTimestamp() {
            var from = new Date(scope.selectedDate.toDate().getTime());
            var fromTime = scope.selectedStart;
            from.setHours(fromTime.getHours(), fromTime.getMinutes(), 0, 0);

            var to = new Date(scope.selectedDate.toDate().getTime());
            var toTime = scope.selectedEnd;
            to.setHours(toTime.getHours(), toTime.getMinutes(), 0, 0);

            return {
              "from": Math.floor(from.getTime() / 1000),
              "to": Math.floor(to.getTime() / 1000)
            };
          }

          // Watch for changes to bookings.
          scope.$watch('bookings',
            function (val) {
              if (!val) return;

              // Indicate the the calendar has not been loaded.
              scope.loaded = false;

              // Render timeIntervals for calendar view.
              renderCalendar();

              // Calendar done loading.
              scope.loaded = true;
            }
          );
        },
        templateUrl: '/modules/koba_booking/js/app/pages/calendar/booking-calendar.html'
      };
    }
  ]);
