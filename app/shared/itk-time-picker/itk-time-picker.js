/**
 * @file
 * Contains an angular wrapper for the datepicker jquery plugin.
 */

/**
 * Angular timepicker
 */
angular.module('itkTimePicker', [])
  .directive('itkTimePicker', function () {
    return {
      restrict: 'E',
      require: '',
      scope: {
        "time": "=",
        "step": "="
      },
      link: function (scope) {
        scope.inc = function() {
          var newTime = scope.time.getTime() + scope.step * 60 * 1000;
          if (newTime > 24 * 60 * 60 * 1000) {
            newTime = 24 * 60 * 60 * 1000;
          }
          scope.time = new Date(newTime);
        };
        scope.dec = function() {
          var newTime = scope.time.getTime() - scope.step * 60 * 1000;
          if (newTime < 0) {
            newTime = 0;
          }
          scope.time = new Date(newTime);
        };

        scope.getHours = function() {
          var hours = "" + scope.time.getUTCHours();
          if (hours.length === 1) {
            hours = "0" + hours;
          }
          return hours;
        };

        scope.getMinutes = function() {
          var minutes = "" + scope.time.getUTCMinutes();
          if (minutes.length === 1) {
            minutes = "0" + minutes;
          }
          return minutes;
        }
      },
      templateUrl: '/modules/koba_booking/app/shared/itk-time-picker/template/itk-time-picker.html'
    }
  });
