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
        }).success(function (response) {
          defer.resolve(response);
        }).error(function (error) {
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
        }).success(function (response) {
          defer.resolve(response);
        }).error(function (error) {
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
          "bufferStart": "=",
          "bufferEnd": "="
        },
        link: function (scope) {
          var bookings = [];

          // Used for.
          scope.loaded = false;

          // Get selected timestamps.
          var selectedTimestamp = getSelectedDateTimesAsTimestamps();

          // Watch for changes to date and time selections.
          scope.$watchGroup(['selectedStart', 'selectedEnd', 'selectedDate'],
            function (val) {
              if (!val) {
                return;
              }

              // Update selected time stamps.
              selectedTimestamp = getSelectedDateTimesAsTimestamps();
            }
          );

          // Watch for changes to bookings.
          scope.$watch('bookings',
            function (val) {
              if (!val) {
                return;
              }

              // Indicate the the calendar has not been loaded.
              scope.loaded = false;

              // Render timeIntervals for calendar view.
              renderCalendar();

              // Calendar done loading.
              scope.loaded = true;
            }
          );

          /**
           * Is the time interval selected.
           *
           * @param timeInterval
           *   The time interval to evaluate.
           * @returns boolean|string
           *   The result of the evaluation. This can be true/false/'first'. 'first' is if the time interval is selected and the first in the selection.
           */
          scope.selected = function selected(timeInterval) {
            var selectedTime = timeInterval.time.getTime();

            if (selectedTimestamp.from <= selectedTime && selectedTimestamp.to > selectedTime) {
              if (selectedTimestamp.from === selectedTime) {
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
           * Is the time interval inside the buffers but not the selection?
           *
           * @returns boolean
           *   The result of the evaluation.
           */
          scope.inBuffer = function inBuffer(timeInterval) {
            var evaluationTime = timeInterval.time.getTime();

            return (
              (evaluationTime >= selectedTimestamp.fromBuffer && evaluationTime < selectedTimestamp.from) ||
              (evaluationTime >= selectedTimestamp.to && evaluationTime < selectedTimestamp.toBuffer)
            );
          };

          /**
           * Extend the current time interval or move to another time interval.
           *
           * Select a time interval and 1 hour forward.
           *
           * @param timeInterval
           *   The clicked time interval.
           */
          scope.select = function select(timeInterval) {
            var timestamp = timeInterval.time.getTime();

            if (selectedTimestamp.from === timestamp) {
              return;
            }

            var selectedStart = new Date(timestamp);
            var selectedEnd = new Date(timestamp + (selectedTimestamp.to - selectedTimestamp.from));

            // Check that the new selectedEnd will not overlap the available time slots.
            if (selectedStart.getHours() > selectedEnd.getHours() ||
              (selectedEnd.getHours() > parseInt(scope.interestPeriod.end) ||
              ((selectedEnd.getHours() === parseInt(scope.interestPeriod.end) && selectedEnd.getMinutes() > 0)))) {
              return;
            }

            // Set the new start and end times based on the interval selected. The end time is the start plus the
            // selected interval in the time picker.
            scope.selectedStart = selectedStart;
            scope.selectedEnd = selectedEnd;
          };

          /**
           * Expose the Drupal.t() function to angular templates.
           *
           * @param str
           *   The string to translate.
           * @returns string
           *   The translated string.
           */
          scope.Drupal = {
            "t": function (str) {
              return $window.Drupal.t(str);
            }
          };

          /**
           * Render the calendar.
           *
           * Generates the calendar timeIntervals.
           * - based on
           *   - interest period
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

              // Get the Date representation of time.
              var timeDate = time.toDate();

              // See if the time interval is free.
              var free = true;
              for (var j = 0; j < scope.bookings.length; j++) {
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
                'halfhour': (time.minutes() > 0),
                'booked': !free
              });
            }
          }

          /**
           * Get current date/time selections as javascript timestamps.
           *
           * Build timestamps to send to the server based on the date picker and time picker selector. The first issue is
           * that the time picker returns date information for the time selected today, while we want to combine the date
           * select and only get the time selected (without the date from the time picker).
           *
           * @returns {{from: number, to: number}}
           */
          function getSelectedDateTimesAsTimestamps() {
            if (scope.selectedDate !== null && scope.selectedStart !== null) {
              var from = new Date(scope.selectedDate.toDate().getTime());
              var fromTime = scope.selectedStart;
              from.setHours(fromTime.getHours(), fromTime.getMinutes(), 0, 0);

              var to = new Date(scope.selectedDate.toDate().getTime());
              var toTime = scope.selectedEnd;
              to.setHours(toTime.getHours(), toTime.getMinutes(), 0, 0);

              return {
                "from": from.getTime(),
                "to": to.getTime(),
                "fromBuffer": from.getTime() - parseInt(Math.ceil(scope.bufferStart / 30.0) * 30) * 60 * 1000,
                "toBuffer": to.getTime() + parseInt(Math.ceil(scope.bufferEnd / 30.0) * 30) * 60 * 1000
              };
            }

            return {
              "from": 0,
              "to": 0,
              "fromBuffer": 0,
              "toBuffer": 0
            };
          }
        },
        templateUrl: '/modules/koba_booking/js/app/pages/calendar/booking-calendar.html'
      };
    }
  ]);
