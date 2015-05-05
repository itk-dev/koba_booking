/**
 * @file
 * Contains booking-calendar directive.
 */

/**
 * Booking calendar directive.
 */
angular.module('kobaApp')
  .factory('kobaFactory', ['$http', '$q',
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
  .directive('bookingCalendar', ['kobaFactory',
    function (kobaFactory) {
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

          // @TODO: refactor.
          scope.selected = function(timeInterval) {
            var startMoment = moment(scope.selectedDate).add(scope.selectedStart, 'milliseconds');
            var endMoment = moment(scope.selectedDate).add(scope.selectedEnd, 'milliseconds');

            return startMoment <= timeInterval.timeMoment && endMoment > timeInterval.timeMoment;
          };

          function renderCalendar() {
            scope.timeIntervals = [];

            scope.interestPeriodEntries =
              (scope.interestPeriod.end.hours() - scope.interestPeriod.start.hours()) * 2 +
              (scope.interestPeriod.end.minutes() - scope.interestPeriod.start.minutes()) % 30;

            // Render calendar.
            for (var i = 0; i < scope.interestPeriodEntries; i++) {
              var time = moment(scope.interestPeriod.start).add(scope.selectedDate.format('x'), 'milliseconds').add(i * 30, 'minutes');

              var disabled = false;
              for (var j = 0; j < scope.disabled.length; j++) {
                if (time >= moment(parseInt(scope.selectedDate.format('x')) + parseInt(scope.disabled[j][0])) &&
                  time < moment(parseInt(scope.selectedDate.format('x')) + parseInt(scope.disabled[j][1]))) {
                  disabled = true;
                  break;
                }
              }

              var timeDate = time.toDate();

              var free = true;
              for (j = 0; j < bookings.length; j++) {
                if (timeDate.getTime() >= bookings[j].start * 1000 &&
                  timeDate.getTime() < bookings[j].end * 1000) {
                  free = false;
                  break;
                }
              }

              scope.timeIntervals.push({
                'time': timeDate,
                'timeMoment': time,
                'halfhour': (time.minutes() > 0),
                'disabled': disabled,
                'booked': !free
              });
            }
          }

          // Watch for changes to selectedDate and selectedResource.
          // Update the calendar view accordingly.
          scope.$watchGroup(['selectedDate', 'selectedResource'],
            function (val) {
              if (!val) return;
              if (!scope.selectedResource || !scope.selectedDate) return;

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
                  renderCalendar();
                  scope.loaded = true;
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
