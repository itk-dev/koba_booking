/**
 * @file
 * @TODO: missing description.
 */

angular.module('kobaApp').controller("CalendarController", ['$scope', '$window', 'kobaFactory',
  function ($scope, $window, kobaFactory) {
    /**
     * Expose the Drupal.t() function to angularjs templates.
     *
     * @param str
     *   The string to translate.
     * @returns string
     *   The translated string.
     */
    $scope.t = function(str) {
      return $window.Drupal.t(str);
    };

    $scope.modulePath = '/' + drupalSettings['koba_booking']['module_path'];
    $scope.themePath = '/' + drupalSettings['koba_booking']['theme_path'];

    // Get booking information from drupalSettings.
    var initBooking = {
      "resource": drupalSettings['koba_booking']['resource'],
      "from": drupalSettings['koba_booking']['from'],
      "to": drupalSettings['koba_booking']['to']
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

    if (initBooking.from && initBooking.to) {
      $scope.selected.date = moment(initBooking.from * 1000).startOf('day');

      $scope.selected.time.start = new Date((initBooking.from - parseInt($scope.selected.date.format('X'))) * 1000);
      $scope.selected.time.end = new Date((initBooking.to - parseInt($scope.selected.date.format('X'))) * 1000);
    }

    // Load available resources.
    $scope.resources = [];
    kobaFactory.getResources().then(
      function success(data) {
        $scope.resources = data;

        for (var i = 0; i < $scope.resources.length; i++) {
          if ($scope.resources[i].id === initBooking.resource) {
            $scope.selected.resource = $scope.resources[i];
          }
        }
      },
      function error(error) {
        // @TODO: Report error properly.
        console.error(error);
      }
    );

    // Interest period to show.
    // @TODO: Make this configurable.
    $scope.interestPeriod = {
      "start": 6,
      "end": 24
    };

    // Disabled intervals.
    // @TODO: Make this configurable.
    $scope.disabled = [
      [6,7], [23,24]
    ];

    /**
     * Return the link.
     *
     * @TODO: Avoid hardcoded link
     */
    $scope.getLink = function getLink() {
      if (!$scope.selected.resource) {
        return null;
      }

      var from = moment($scope.selected.date).add($scope.selected.time.start.getTime(), 'milliseconds');
      var to = moment($scope.selected.date).add($scope.selected.time.end.getTime(), 'milliseconds');

      return encodeURI('/booking/wayf/login?res=' + $scope.selected.resource.id + '&from=' + from.format('X') + '&to=' + to.format('X'));
    };

    /**
     * Set the selected resource.
     *
     * @param resource
     *   The resource to select.
     */
    $scope.setResource = function setResource(resource) {
      $scope.selected.resource = resource;
      $scope.pickResource = false;
    };

    /**
     * Get selected date.
     *
     * @returns Date
     *   The Date representation of scope.selected.date
     */
    $scope.getSelectedDate = function() {
      return $scope.selected.date.toDate();
    };

    /**
     * Get selected start time.
     *
     * @returns string
     *   String representation of the selected start time.
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
     *
     * @returns string
     *   String representation of the selected end time.
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
     * Get selected resource.
     * @returns Date
     */
    $scope.getSelectedResource = function() {
      if ($scope.selected.resource) {
        return $scope.selected.resource.name;
      }
      return null;
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
      var now = moment();

      if ($scope.selected.date.dayOfYear() + $scope.selected.date.year() * 365 > now.dayOfYear() + now.year() * 365) {
        $scope.selected.date = moment($scope.selected.date.add(-1, 'day'));
      }
    };

    /**
     * Go to next date.
     */
    $scope.nextDate = function() {
      $scope.selected.date = moment($scope.selected.date.add(1, 'day'));
    };

    /**
     * Go to the previous resource.
     */
    $scope.prevResource = function() {
      if (!$scope.selected.resource) {
        $scope.selected.resource = $scope.resources[0];
      } else {
        for (var i = 0; i < $scope.resources.length; i++) {
          var res = $scope.resources[i];
          if (res.mail === $scope.selected.resource.mail) {
            if (i === 0) {
              $scope.selected.resource = $scope.resources[$scope.resources.length - 1];
            }
            else {
              $scope.selected.resource = $scope.resources[i - 1];
            }
            return;
          }
        }
      }
    };

    /**
     * Go to the next resource.
     */
    $scope.nextResource = function() {
      if (!$scope.selected.resource) {
        $scope.selected.resource = $scope.resources[0];
      } else {
        for (var i = 0; i < $scope.resources.length; i++) {
          var res = $scope.resources[i];
          if (res.mail === $scope.selected.resource.mail) {
            $scope.selected.resource = $scope.resources[(i + 1) % $scope.resources.length];
            return;
          }
        }
      }
    };

    /**
     * Show/hide date picker.
     */
    $scope.toggleDate = function() {
      $scope.pickDate = !$scope.pickDate;
      var browserSize =  document.body.clientWidth;
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
     *
     * @TODO: Should this use moment to do calculations?
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
     *
     * @TODO: Should this use moment to do calculations?
     */
    $scope.$watch('selected.time.end', function(val) {
      if (!val) return;

      if ($scope.selected.time.end <= $scope.selected.time.start) {
        $scope.selected.time.start = new Date($scope.selected.time.end.getTime() - 30 * 60 * 1000);
      }
    });
  }
]);
