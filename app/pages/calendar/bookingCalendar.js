/**
 * @file
 * Contains booking-calendar directive.
 */

/**
 * Booking calendar directive.
 */
angular.module('kobaApp')
  .factory('bookingFactory', ['$http', '$q',
    function ($http, $q) {
      'use strict';

      var factory = {};

      factory.getBookings = function getBookings(resource, from, to) {
        var defer = $q.defer();

        $http({
          url: '/admin/booking/api/resource?res=' + resource + '&from=' + from + '&to=' + to,
          method: 'GET',
          headers: {
            "Content-Type": "text/html",
            "Accept": "text/html"
          }

        }).success(function(response){
          console.log(response);
        }).error(function(error){
          console.log(error);
        });

        return defer.promise;
      };

      return factory;
    }
  ])
  .directive('bookingCalendar', ['bookingFactory',
    function (bookingFactory) {
      return {
        restrict: 'E',
        scope: {
          "selectedDate": "=",
          "selectedStart": "=",
          "selectedEnd": "=",
          "bookings": "=",
          "interestPeriod": "=",
          "disabled": "="
        },
        link: function (scope, el, attrs) {
          scope.timeIntervals = [];

          bookingFactory.getBookings('fisk', 123, 1234);

          scope.interestPeriodEntries =
            (scope.interestPeriod.end.hours() - scope.interestPeriod.start.hours()) * 2 +
            (scope.interestPeriod.end.minutes() - scope.interestPeriod.start.minutes()) % 30;

          scope.$watch('selectedDate',
            function (val) {
              if (!val) return;

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
                  'halfhour': (time.minutes() > 0),
                  'disabled': disabled,
                  'free': i % 2,
                  'selected': i % 2
                });
              }
            }
          );
        },
        templateUrl: '/modules/koba_booking/app/pages/calendar/booking-calendar.html'
      }
    }
  ]);
