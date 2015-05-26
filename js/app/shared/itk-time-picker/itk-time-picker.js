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
        "step": "="
      },
      link: function (scope) {
        /**
         * @TODO: Missing function description
         */
        scope.inc = function inc() {
          var newTime = scope.time.getTime() + scope.step * 60 * 1000;
          if (newTime > 24 * 60 * 60 * 1000) {
            newTime = 24 * 60 * 60 * 1000;
          }
          scope.time = new Date(newTime);
        };

        /**
         * @TODO: Missing function description
         */
        scope.dec = function dec() {
          var newTime = scope.time.getTime() - scope.step * 60 * 1000;
          if (newTime < 0) {
            newTime = 0;
          }
          scope.time = new Date(newTime);
        };

        /**
         * @TODO: Missing function description
         */
        scope.getHours = function getHours() {
          var hours = "" + scope.time.getUTCHours();
          if (hours.length === 1) {
            hours = "0" + hours;
          }
          return hours;
        };

        /**
         * @TODO: Missing function description
         */
        scope.getMinutes = function getMinutes() {
          var minutes = "" + scope.time.getUTCMinutes();
          if (minutes.length === 1) {
            minutes = "0" + minutes;
          }
          return minutes;
        };

        scope.modulePath = '/' + drupalSettings['koba_booking']['module_path'];
        scope.themePath = '/' + drupalSettings['koba_booking']['theme_path'];
        scope.app_dir = '/' + drupalSettings['koba_booking']['app_dir'];
      },
      templateUrl: drupalSettings['koba_booking']['app_dir'] + '/shared/itk-time-picker/template/itk-time-picker.html'
    };
  });
