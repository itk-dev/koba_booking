angular.module('kobaApp').controller("CalendarController", ['$scope', '$window',
  function ($scope, $window) {
    // Open up for translations.
    $scope.t = function(str) {
      return $window.Drupal.t(str);
    };

    // Defaults: Start of today
    $scope.selected = {
      "date": moment().startOf('day'),
      "time": {
        "start": (new Date(0)).setHours(7),
        "end": (new Date(0)).setHours(8)
      }
    };

    $scope.interestPeriod = {
      "start": moment().startOf('day').add(5, 'hours'),
      "end": moment().startOf('day').add(21, 'hours')
    };
    $scope.disabled = [
      [moment().startOf('day').add(5, 'hours'), moment().startOf('day').add(7, 'hours')],
      [moment().startOf('day').add(19, 'hours'), moment().startOf('day').add(21, 'hours')]
    ];

    /**
     * Get selected date.
     * @returns Date
     */
    $scope.getSelectedDate = function() {
      return $scope.selected.date.toDate();
    };

    /**
     * Get selected start time.
     * @returns {*}
     */
    $scope.getSelectedStartTime = function() {
      return $scope.selected.time.start;
    };

    /**
     * Get selected end time.
     * @returns {*}
     */
    $scope.getSelectedEndTime = function() {
      return $scope.selected.time.end;
    };

    /**
     * Show/hide time picker.
     */
    $scope.toggleTime = function() {
      $scope.pickTime = !$scope.pickTime;
    };

    /**
     * Show/hide date picker.
     */
    $scope.toggleDate = function() {
      $scope.pickDate = !$scope.pickDate;

      jQuery('html').toggleClass('is-locked');
      jQuery('body').toggleClass('is-locked');
    };

    $scope.$watch('selected.time.start', function(val) {
      if (!val) return;

      if ($scope.selected.time.end < $scope.selected.time.start) {
        $scope.selected.time.end = new Date($scope.selected.time.start.getTime() + 30 * 60 * 1000);
      }
    });
  }
]);