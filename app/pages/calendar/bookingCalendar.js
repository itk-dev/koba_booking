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
       * @TODO
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
       * @TODO
       *
       * @param resource
       * @param from
       * @param to
       * @returns {*}
       */
      factory.getBookings = function getBookings(resource, from, to) {
        var defer = $q.defer();

        console.log(resource);
        console.log(from);
        console.log(to);

        $http({
          url: '/admin/booking/api/resource?res=' + resource + '&from=' + from + '&to=' + to,
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
        link: function (scope, el, attrs) {
          var bookings = [];
          scope.loaded = false;

          scope.timeIntervals = [];

          scope.interestPeriodEntries =
            (scope.interestPeriod.end.hours() - scope.interestPeriod.start.hours()) * 2 +
            (scope.interestPeriod.end.minutes() - scope.interestPeriod.start.minutes()) % 30;

          // @TODO: refactor.
          scope.selected = function(timeInterval) {
            var startMoment = moment(scope.selectedDate).add(scope.selectedStart, 'milliseconds');
            var endMoment = moment(scope.selectedDate).add(scope.selectedEnd, 'milliseconds');

            return startMoment <= timeInterval.timeMoment && endMoment > timeInterval.timeMoment;
          };

          // Render calendar.
          for (var i = 0; i < scope.interestPeriodEntries; i++) {
            var time = moment(scope.interestPeriod.start);
            time.add(i * 30, 'minutes');

            var disabled = false;
            for (var j = 0; j < scope.disabled.length; j++) {
              if (time >= scope.disabled[j][0] && time < scope.disabled[j][1]) {
                disabled = true;
                break;
              }
            }

            scope.timeIntervals.push({
              'time': time.toDate(),
              'timeMoment': time,
              'halfhour': (time.minutes() > 0),
              'disabled': disabled
            });
          }

          scope.free = function free(timeInterval) {
            for (var i = 0; i < bookings.length; i++) {
              if (timeInterval.timeMoment >= moment(bookings[i].start_time * 1000) &&
                  timeInterval.timeMoment < moment(bookings[i].end_time * 1000)) {
                return false;
              }
            }

            return true;
          };

          scope.$watchGroup(['selectedDate', 'selectedResource'],
            function (val) {
              if (!val) return;
              if (!scope.selectedResource || !scope.selectedDate) return;

              kobaFactory.getBookings(scope.selectedResource.mail, parseInt(moment(scope.selectedDate).startOf('day').toDate() / 1000), parseInt(moment(scope.selectedDate).endOf('day').toDate() / 1000)).then(
                function success(data) {
                  bookings = data;
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
