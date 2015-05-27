/**
 * @file
 * Contains an angular wrapper for the date picker jquery plugin.
 */

/**
 * Angular time picker.
 */
angular.module('itkTimePicker', [])
  .directive('itkTimePicker', function () {
    "use strict";

    return {
      restrict: 'E',
      require: '',
      scope: {
        "time": "=",
        "step": "=",
        "offset": "=",
        "interestPeriod": "="
      },
      link: function (scope) {
        /**
         * Select the next time based on step and prevent overflow into next day.
         */
        scope.inc = function inc() {
          var newTime = scope.time.getTime() + scope.step * 60 * 1000;

          // Get end of day to ensure that time stays within interest period.
          var endOfDay = new Date();
          endOfDay.setHours(scope.interestPeriod.end, 0, 0, 0);

          // Only set new selection, if not into next day.
          if (newTime < endOfDay.getTime()) {
            scope.time = new Date(newTime);
          }
        };

        /**
         * Go one step down on the selected time.
         */
        scope.dec = function dec() {
          var newTime = scope.time.getTime() - scope.step * 60 * 1000;

          // Get end of day to ensure that time stays within interest period.
          var startOfDay = new Date();
          startOfDay.setHours(scope.interestPeriod.start, 0, 0, 0);

          if (newTime >= startOfDay) {
            scope.time = new Date(newTime);
          }
        };

        /**
         * Get current selected hours.
         */
        scope.getHours = function getHours() {
          var hours = "" + scope.time.getHours();
          if (hours.length === 1) {
            hours = "0" + hours;
          }

          return hours;
        };

        /**
         * Get currently selected minutes.
         */
        scope.getMinutes = function getMinutes() {
          var minutes = "" + scope.time.getMinutes();
          if (minutes.length === 1) {
            minutes = "0" + minutes;
          }

          return minutes;
        };

        // Set default time, if not given.
        if (!scope.time) {
          var date = new Date();

          // Set current time plus one hour into the feature.
          date.setHours(date.getHours() + Math.round(date.getMinutes()/60) + 1);

          // If offset is set, set the minutes to it.
          date.setMinutes(scope.offset ? scope.offset : 0, 0, 0);
          scope.time = date;
        }

        // Set configuration.
        scope.modulePath = '/' + drupalSettings['koba_booking']['module_path'];
        scope.themePath = '/' + drupalSettings['koba_booking']['theme_path'];
        scope.app_dir = '/' + drupalSettings['koba_booking']['app_dir'];
      },
      templateUrl: drupalSettings['koba_booking']['app_dir'] + '/shared/itk-time-picker/template/itk-time-picker.html'
    };
  });
