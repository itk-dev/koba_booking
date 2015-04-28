/**
 * @file
 * Contains an angular wrapper for the timepicker jquery plugin.
 */

/**
 * Angular wrapper for jquery.timepicker.js.
 */
angular.module('timePicker', [])
.directive('timePicker', function() {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, el) {
      el.timepicker({
        'timeFormat': 'H:i',
        'step': function step() {
          return 30;
        },
        'forceRoundTime': true
      });
    }
  }
});
