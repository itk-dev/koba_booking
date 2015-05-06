angular.module('kobaApp').controller("CalendarController", ['$scope', '$window', 'kobaFactory',
  function ($scope, $window, kobaFactory) {
    // Open up for translations.
    $scope.t = function(str) {
      return $window.Drupal.t(str);
    };

    // Defaults: Start of today
    // For time we use a regular date to integrate with timepicker.
    $scope.selected = {
      "date": moment().startOf('day'),
      "time": {
        "start": (new Date(7 * 60 * 60 * 1000)),
        "end": (new Date(8 * 60 * 60 * 1000))
      },
      "resource": null
    };

    // Interest period to show.
    $scope.interestPeriod = {
      "start": 6,
      "end": 24
    };

    // Disabled intervals.
    $scope.disabled = [
      [6,7], [23,24]
    ];

    // Load available resources.
    $scope.resources = [];
    kobaFactory.getResources().then(
      function success(data) {
        $scope.resources = data;
      },
      function error(error) {
        // @TODO: Report error properly.
        console.error(error);
      }
    );

    $scope.setResource = function setResource(resource) {
      $scope.selected.resource = resource;
    };

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
      var hours = "" + $scope.selected.time.start.getUTCHours();
      if (hours.length === 1) {
        hours = "0" + hours;
      }
      var minutes = "" + $scope.selected.time.start.getUTCMinutes();
      if (minutes.length === 1) {
        minutes = "0" + minutes;
      }
      return hours + ":" + minutes;
    };

    /**
     * Get selected end time.
     * @returns {*}
     */
    $scope.getSelectedEndTime = function() {
      var hours = "" + $scope.selected.time.end.getUTCHours();
      if (hours.length === 1) {
        hours = "0" + hours;
      }
      var minutes = "" + $scope.selected.time.end.getUTCMinutes();
      if (minutes.length === 1) {
        minutes = "0" + minutes;
      }
      return hours + ":" + minutes;
    };

    /**
     * Show/hide time picker.
     */
    $scope.toggleTime = function() {
      $scope.pickTime = !$scope.pickTime;
    };

    /**
     * Go to previous date.
     *   Not before today.
     */
    $scope.prevDate = function() {
      if ($scope.selected.date.day() > moment().day()) {
        $scope.selected.date.add(-1, 'day');
      }
    };

    /**
     * Go to next date.
     */
    $scope.nextDate = function() {
      $scope.selected.date.add(1, 'day');
    };

    /**
     * Show/hide date picker.
     */
    $scope.toggleDate = function() {
      $scope.pickDate = !$scope.pickDate;

      jQuery('html').toggleClass('is-locked');
      jQuery('body').toggleClass('is-locked');
    };

    /**
     * Show/hide resource picker.
     */
    $scope.toggleResource = function() {
      $scope.pickResource = !$scope.pickResource;
    };

    /**
     * Impose constraints on start time.
     * - Never more than end, push end time forward.
     */
    $scope.$watch('selected.time.start', function(val) {
      if (!val) return;

      if ($scope.selected.time.end <= $scope.selected.time.start) {
        $scope.selected.time.end = new Date($scope.selected.time.start.getTime() + 30 * 60 * 1000);
      }
    });

    /**
     * Impose constraints on end time.
     * - Never less than start time, push start time back.
     */
    $scope.$watch('selected.time.end', function(val) {
      if (!val) return;

      if ($scope.selected.time.end <= $scope.selected.time.start) {
        $scope.selected.time.start = new Date($scope.selected.time.end.getTime() - 30 * 60 * 1000);
      }
    });
  }
]);