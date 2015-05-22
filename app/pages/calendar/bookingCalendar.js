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
       * @TODO: Missing parameter documentation?
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

          // Initialize start and end moment objects.
          // Used for comparisons, updated when scope.selectedStart and scope.selectedEnd are updated.
          var startTimestamp = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
          var endTimestamp = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();

          // Watch for changes to selectedStart and selectedEnd.
          // Update startTimestamp and endTimestamp.
          scope.$watchGroup(['selectedStart', 'selectedEnd', 'selectedDate'],
            function (val) {
              if (!val) return;
              startTimestamp = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
              endTimestamp = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();
            }
          );

          /**
           * Expose the Drupal.t() function to angularjs templates.
           *
           * @param str
           *   The string to translate.
           * @returns string
           *   The translated string.
           */
          scope.t = function(str) {
            return $window.Drupal.t(str);
          };

          /**
           * Is the time interval selected?
           *   returns 'first', 'middle', 'last', 'first-last'
           *     corresponding to whether it is the first, middle, last, first-last (both first and last) of a booking.
           *     used for which class to attach to the time interval.
           *
           * @TODO: Refactor so this method is called so much.
           *
           * @param timeInterval
           *   The time interval to evaluate.
           * @returns boolean|string
           *   The result of the evaluation.
           */
          scope.selected = function(timeInterval) {
            var t = parseInt(timeInterval.timeMoment.format('x'));

            if (startTimestamp <= t && endTimestamp > t) {
              if (startTimestamp == t) {
                if (endTimestamp == t + 30 * 60 * 1000) {
                  return 'first-last';
                }
                else {
                  return 'first';
                }
              }
              else if (endTimestamp == t + 30 * 60 * 1000) {
                return 'last';
              }
              else {
                return 'middle';
              }
            } else {
              return false;
            }
          };

          /**
           * Extend the current time interval or move to another time interval.
           * Select a time interval and 1 hour forward.
           *
           * @param timeInterval
           *   The clicked time interval.
           */
          scope.select = function (timeInterval) {
            if (startTimestamp === timeInterval.timeMoment) return;

            var difference = endTimestamp - startTimestamp;

            scope.selectedStart = new Date(
              timeInterval.timeFromZero.hours * 60 * 60 * 1000 +
              timeInterval.timeFromZero.minutes * 60 * 1000
            );

            scope.selectedEnd = (new Date(scope.selectedStart.getTime() + difference));
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

            // The last time interval's type.
            var lastType = null;

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

              // Set text.
              var text = '';
              if (!disabled) {
                if (free) {
                  // Make sure the text is not repeated.
                  if (lastType !== 'Free') {
                    text = 'Free';
                    lastType = 'Free';
                  }
                }
                else {
                  // Make sure the text is not repeated.
                  if (lastType !== 'Booked') {
                    text = 'Booked';
                    lastType = 'Booked';
                  }
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
                'booked': !free,
                'text': text
              });
            }
          }

          // Watch for changes to bookings
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
        templateUrl: '/modules/koba_booking/app/pages/calendar/booking-calendar.html'
      };
    }
  ]);
