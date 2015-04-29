/**
 * @file
 * Contains an angular wrapper for the datepicker jquery plugin.
 */

/**
 * Angular wrapper for jquery.datepicker.js.
 */
angular.module('datePicker', [])
.directive('datePicker', function() {
  return {
    restrict: 'A',
    require: '^ngModel',
    scope: {
      "numberOfMonths": "=",
      "showWeekNumber": "="
    },
    link: function(scope, el, attrs, ngModel) {
      // Setup datepicker.
      el.datepicker({
        numberOfMonths: scope.numberOfMonths ? scope.numberOfMonths : 1,
        showButtonPanel: false,
        onSelect: function() {
          scope.$apply(function() {
            ngModel.$setViewValue(moment(el.datepicker('getDate')));
          });
        },
        showWeek: scope.showWeekNumber ? scope.showWeekNumber : false,
        hideIfNoPrevNext: true
      });
      // Set danish
      el.datepicker(jQuery.datepicker.regional["da"]);
      el.datepicker("option", "minDate", new Date());
      el.datepicker("option", "maxDate", "+8m");

      // update the datepicker whenever the value on the scope changes
      ngModel.$render = function() {
        el.datepicker('setDate', moment(ngModel.$modelValue).toDate());
        el.change();
      };
    }
  }
});
