/**
 * @file
 * Contains booking-calendar directive.
 */

/**
 * Booking calendar directive.
 */
angular.module("kobaApp")
  .factory("kobaFactory", ['$http', '$q',
    function ($http, $q) {
      'use strict';

      var factory = {};

      /**
       * Get available resources from Koba.
       *
       * @returns {*}
       */
      factory.getResources = function getResources() {
        var defer = $q.defer();

        $http({
          url: '/admin/booking/api/resources',
          method: 'GET',
          headers: {
            "Content-Type": "text/html",
            "Accept": "text/html"
          }
        }).success(function(response){
          defer.resolve(response);
        }).error(function(error){
          defer.reject(error);
        });

        return defer.promise;
      };

      /**
       * Get bookings from Koba.
       *
       * @param resource
       * @param from
       * @param to
       * @returns {*}
       */
      factory.getBookings = function getBookings(resource, from, to) {
        var defer = $q.defer();

        $http({
          url: '/admin/booking/api/bookings?res=' + resource + '&from=' + from + '&to=' + to,
          method: 'GET',
          headers: {
            "Content-Type": "text/html",
            "Accept": "text/html"
          }
        }).success(function(response){
          defer.resolve(response);
        }).error(function(error){
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
          scope.loaded = false;

          var startMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
          var endMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();

          // Open up for translations.
          scope.t = function(str) {
            return $window.Drupal.t(str);
          };

          /**
           * Is the time interval selected?
           *   returns 'first', 'middle', 'last'
           *     corresponding to whether it is the first, middle or last of a booking.
           *
           *
           * @param timeInterval
           * @returns {*}
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
           * Select a time interval and 1 hour forward.
           * @param timeInterval
           */
          scope.select = function (timeInterval) {
            scope.selectedStart = new Date(timeInterval.timeFromZero.hours * 60 * 60 * 1000 + timeInterval.timeFromZero.minutes * 60 * 1000);
          };

          /**
           * Increase end time by half an hour.
           */
          scope.incTime = function incTime() {
            var newTime = scope.selectedEnd.getTime() + 30 * 60 * 1000;
            if (newTime > 24 * 60 * 60 * 1000) {
              newTime = 24 * 60 * 60 * 1000;
            }
            scope.selectedEnd = new Date(newTime);
          };

          /**
           * Decrease end time by half an hour.
           */
          scope.decTime = function decTime() {
            var newTime = scope.selectedEnd.getTime() - 30 * 60 * 1000;
            if (newTime < 0) {
              newTime = 0;
            }
            scope.selectedEnd = new Date(newTime);
          };

          /**
           * Render the calendar.
           */
          function renderCalendar() {
            scope.timeIntervals = [];

            scope.interestPeriodEntries = (scope.interestPeriod.end - scope.interestPeriod.start) * 2;

            // The last time interval's type.
            var lastType = null;

            // Render calendar.
            for (var i = 0; i < scope.interestPeriodEntries; i++) {
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

              // Set text
              var text = '';
              if (!disabled) {
                if (free) {
                  if (lastType !== 'Free') {
                    text = 'Free';
                    lastType = 'Free';
                  }
                }
                else {
                  if (lastType !== 'Booked') {
                    text = 'Booked';
                    lastType = 'Booked';
                  }
                }
              }

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

          // Watch for changed to selectedStart and selectedEnd.
          // Update startMoment and endMoment, used for scope.selected()
          scope.$watchGroup(['selectedStart', 'selectedEnd'],
            function (val) {
              if (!val) return;
              startMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedStart.getTime();
              endMoment = parseInt(scope.selectedDate.format('x')) + scope.selectedEnd.getTime();
            }
          );

          // Watch for changes to selectedDate and selectedResource.
          // Update the calendar view accordingly.
          scope.$watchGroup(['selectedDate', 'selectedResource'],
            function (val) {
              if (!val) return;
              if (!scope.selectedResource || !scope.selectedDate) return;
              scope.loaded = true;

              scope.loaded = false;

              kobaFactory.getBookings(
                scope.selectedResource.mail,
                moment(scope.selectedDate).startOf('day').format('X'),
                moment(scope.selectedDate).endOf('day').format('X')
              ).then(
                function success(data) {
                  bookings = data;

                  renderCalendar();

                  scope.loaded = true;
                },
                function error(reason) {
                  console.error(reason);
                }
              );
            }
          );
        },
        templateUrl: '/modules/koba_booking/app/pages/calendar/booking-calendar.html'
      }
    }
  ]);
