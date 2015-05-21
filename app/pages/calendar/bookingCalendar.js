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
          var startMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
          var endMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();

          // Watch for changes to selectedStart and selectedEnd.
          // Update startMoment and endMoment.
          scope.$watchGroup(['selectedStart', 'selectedEnd', 'selectedDate'],
            function (val) {
              if (!val) return;
              startMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
              endMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();
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

            if (startMoment <= t && endMoment > t) {
              if (startMoment == t) {
                if (endMoment == t + 30 * 60 * 1000) {
                  return 'first-last';
                }
                else {
                  return 'first';
                }
              }
              else if (endMoment == t + 30 * 60 * 1000) {
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
            if (timeInterval.booked) return;
            if (startMoment === timeInterval.timeMoment) return;
            if (endMoment === timeInterval.timeMoment) return;

            // If not an extension of the current period, select new interval.
            if (timeInterval.timeMoment < startMoment || timeInterval.timeMoment > endMoment) {
              scope.selectedStart = new Date(
                timeInterval.timeFromZero.hours * 60 * 60 * 1000 +
                timeInterval.timeFromZero.minutes * 60 * 1000
              );
              scope.selectedEnd = new Date(
                timeInterval.timeFromZero.hours * 60 * 60 * 1000 +
                (timeInterval.timeFromZero.minutes + 30) * 60 * 1000
              );
            }
            // Extend the current period with one more hour.
            else {
              scope.selectedEnd = new Date(
                timeInterval.timeFromZero.hours * 60 * 60 * 1000 +
                (timeInterval.timeFromZero.minutes + 30) * 60 * 1000
              );
            }
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
              for (j = 0; j < bookings.length; j++) {
                if (timeDate.getTime() >= bookings[j].start * 1000 &&
                  timeDate.getTime() < bookings[j].end * 1000) {
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

          // Watch for changes to selectedDate and selectedResource.
          // Update the calendar view accordingly.
          scope.$watchGroup(['selectedDate', 'selectedResource'],
            function (val) {
              if (!val) return;
              if (!scope.selectedResource || !scope.selectedDate) return;

              // Indicate the the calendar has not been loaded.
              scope.loaded = false;

              // Get bookings for the resource and date.
              kobaFactory.getBookings(
                scope.selectedResource.mail,
                moment(scope.selectedDate).startOf('day').format('X'),
                moment(scope.selectedDate).endOf('day').format('X')
              ).then(
                function success(data) {
                  bookings = data;

                  // Render timeIntervals for calendar view.
                  renderCalendar();

                  // Calendar done loading.
                  scope.loaded = true;
                },
                function error(reason) {
                  // Still allow the user to make a booking request.
                  // @TODO: Report to the user that the it was not possible to get information from exchange about bookings.
                  bookings = [];

                  // Render timeIntervals for calendar view.
                  renderCalendar();

                  scope.loaded = true;
                }
              );
            }
          );
        },
        templateUrl: '/modules/koba_booking/app/pages/calendar/booking-calendar.html'
      };
    }
  ]);
