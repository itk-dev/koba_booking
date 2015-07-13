/**
 * @file
 * Contains the CalendarController.
 */

/**
 * CalendarController
 * belongs to "kobaApp" module
 */
angular.module('kobaApp').controller("CalendarController", ['$scope', '$window', 'kobaFactory',
  function ($scope, $window, kobaFactory) {
    "use strict";

    var selectedResourceIndex = 0;

    /**
     * Initialize the scope.
     *
     * It's called at the bottom of this scope/file.
     */
    function init() {
      $scope.bookings = [];
      $scope.loadingBookings = false;
      $scope.showCalendar = false;
      $scope.errorGettingBookings = false;
      $scope.errorGettingResources = false;
      $scope.validBooking = false;
      $scope.validating = false;
      $scope.timeIntervalLength = 30;

      // Set paths from the backend.
      $scope.modulePath = '/' + drupalSettings.koba_booking.module_path;
      $scope.themePath = '/' + drupalSettings.koba_booking.theme_path;
      $scope.loginPath = drupalSettings.koba_booking.login_path;

      // Set default start values (non-selected).
      $scope.selected = {
        "date": null,
        "time": {
          "start": null,
          "end": null
        },
        "resource": null
      };

      // Interest period to show (in calendar directive).
      $scope.interestPeriod = drupalSettings.koba_booking.interest_period;

      // Last available booking date.
      $scope.lastAvailableBookingDate = new Date(drupalSettings.koba_booking.last_booking_date * 1000);
      $scope.lastAvailableBookingDateMinusHalfYear = new Date(drupalSettings.koba_booking.last_booking_date_minus_half_year * 1000);

      // Search phase.
      $scope.searchPhase = drupalSettings.koba_booking.search_phase;
      $scope.searchPhaseText = drupalSettings.koba_booking.search_phase_text;

      // Get booking information from drupalSettings.
      var initBooking = {
        "resource": drupalSettings.koba_booking.resource,
        "from": drupalSettings.koba_booking.from,
        "to": drupalSettings.koba_booking.to
      };

      // Initialise selected date and start/end time, if set in drupalSettings.
      if (initBooking.from && initBooking.to) {
        $scope.selected.date = moment(initBooking.from * 1000).startOf('day');

        // Make sure the date from the cookie is not from before today.
        var startToday = moment().startOf('day');
        if ($scope.selected.date < startToday) {
          $scope.selected.date = startToday;
        }

        $scope.selected.time.start = new Date((initBooking.from - parseInt($scope.selected.date.format('X'))) * 1000);
        $scope.selected.time.end = new Date((initBooking.to - parseInt($scope.selected.date.format('X'))) * 1000);
      }

      // Load available resources.
      $scope.resources = [];
      kobaFactory.getResources().then(
        function success(data) {
          $scope.resources = data;

          // Find previously selected resource.
          for (var i = 0; i < $scope.resources.length; i++) {
            if ($scope.resources[i].id === initBooking.resource) {
              $scope.selected.resource = $scope.resources[i];
              selectedResourceIndex = i;
              break;
            }
          }
        },
        function error() {
          $scope.errorGettingResources = true;
        }
      );

      /**
       * Impose constraints on start time.
       *  - Never more than end, push end time forward.
       */
      $scope.$watch('selected.time.start', function(val) {
        if (!val) {
          return;
        }

        if ($scope.selected.time.end <= $scope.selected.time.start) {
          // Ensure that the interest period is used.
          var endOfDay = new Date();
          endOfDay.setHours($scope.interestPeriod.end, 0, 0, 0);
          if (endOfDay > $scope.selected.time.end) {
            $scope.selected.time.end = new Date($scope.selected.time.start.getTime() + $scope.timeIntervalLength * 60 * 1000);
          }
        }
      });

      /**
       * Impose constraints on end time.
       *  - Never less than start time, push start time back.
       */
      $scope.$watch('selected.time.end', function(val) {
        if (!val) {
          return;
        }

        if ($scope.selected.time.end <= $scope.selected.time.start) {
          // Ensure that the interest period is used.
          var startOfDay = new Date();
          startOfDay.setHours($scope.interestPeriod.start, 0, 0, 0);
          if (startOfDay < $scope.selected.time.start) {
            $scope.selected.time.start = new Date($scope.selected.time.end.getTime() - $scope.timeIntervalLength * 60 * 1000);
          }
          else {
            // End sure that the booking is a least the interval wide
            $scope.selected.time.end = new Date($scope.selected.time.end.getTime() + $scope.timeIntervalLength * 60 * 1000);
          }
        }
      });

      /**
       * @TODO: Put the watch statements together and explain what they do (what module change they react to and why).
       */
      $scope.$watchGroup(['selected.time.start', 'selected.time.end'],
        function(val) {
          if (!val) {
            return;
          }

          $scope.validating = true;

          validateBooking();
        }
      );

      /**
       * Expose the Drupal.t() function to angular templates.
       *
       * @param str
       *   The string to translate.
       * @returns string
       *   The translated string.
       */
      $scope.Drupal = {
        "t": function(str) {
          return $window.Drupal.t(str);
        }
      };

      // Watch for changes to selectedDate and selectedResource.
      // Update the calendar view accordingly.
      $scope.$watchGroup(['selected.date', 'selected.resource'],
        function (val) {
          // Return if no resource is selected.
          if (!val || !$scope.selected.resource || !$scope.selected.date) {
            return;
          }

          // Update whether the search phase warning should be displayed.
          $scope.displaySearchPhaseWarning = $scope.selected.date >= moment($scope.lastAvailableBookingDateMinusHalfYear) &&
              $scope.selected.date <= moment($scope.lastAvailableBookingDate);

          $scope.validating = true;
          $scope.loadingBookings = true;
          $scope.errorGettingBookings = false;

          // Get bookings for the resource and date.
          kobaFactory.getBookings(
            $scope.selected.resource.mail,
            moment($scope.selected.date).startOf('day').format('X'),
            moment($scope.selected.date).endOf('day').format('X')
          ).then(
            function success(data) {
              $scope.bookings = data;

              $scope.loadingBookings = false;

              validateBooking();
            },
            function error() {
              // Still allow the user to make a booking request.
              $scope.bookings = [];

              $scope.loadingBookings = false;
              $scope.errorGettingBookings = true;
              $scope.validating = false;
            }
          );
        }
      );
    }

    /**
     * Return the link.
     */
    $scope.getLink = function getLink() {
      if (!$scope.selected.resource || !$scope.selected.date || !$scope.selected.time.start) {
        return null;
      }

      var times = getSelecteDateTimesAsUnixTimestamp();

      return encodeURI($scope.loginPath + '?res=' + $scope.selected.resource.id + '&from=' + times.from + '&to=' + times.to);
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
     * @returns bool|Date
     *   The Date representation of scope.selected.date, if set else false.
     */
    $scope.getSelectedDate = function getSelectedDate() {
      var date = false;
      if ($scope.selected.date !== null) {
        date = $scope.selected.date.toDate();
      }

      return date;
    };

    /**
     * Check if time is selected.
     *
     * @returns {boolean}
     *   True if selected else false.
     */
    $scope.isTimeSelected = function isTimeSelected() {
      return $scope.selected.time.start !== null;
    };

    /**
     * Get selected start time.
     *
     * @returns string
     *   String representation of the selected start time.
     *   HH.mm
     */
    $scope.getSelectedStartTime = function() {
      var hours = "" + $scope.selected.time.start.getHours();
      if (hours.length === 1) {
        hours = "0" + hours;
      }

      var minutes = "" + $scope.selected.time.start.getMinutes();
      if (minutes.length === 1) {
        minutes = "0" + minutes;
      }

      return hours + "." + minutes;
    };

    /**
     * Get selected end time.
     *
     * @returns string
     *   String representation of the selected end time.
     *   HH.mm
     */
    $scope.getSelectedEndTime = function getSelectedEndTime() {
      var hours = "" + $scope.selected.time.end.getHours();
      if (hours.length === 1) {
        hours = "0" + hours;
      }

      var minutes = "" + $scope.selected.time.end.getMinutes();
      if (minutes.length === 1) {
        minutes = "0" + minutes;
      }

      return hours + "." + minutes;
    };

    /**
     * Get selected resource.
     * @returns Date
     */
    $scope.getSelectedResource = function getSelectedResource() {
      if ($scope.selected.resource) {
        return $scope.selected.resource.name;
      }
      return null;
    };

    /**
     * Go to previous date.
     *   Not before today.
     */
    $scope.prevDate = function prevDate() {
      var now = moment();

      if ($scope.selected.date.dayOfYear() + $scope.selected.date.year() * 365 > now.dayOfYear() + now.year() * 365) {
        $scope.selected.date = moment($scope.selected.date.add(-1, 'day'));
      }
    };

    /**
     * Go to next date.
     */
    $scope.nextDate = function nextDate() {
      $scope.selected.date = moment($scope.selected.date.add(1, 'day'));
    };

    /**
     * Go to the previous resource.
     */
    $scope.prevResource = function prevResource() {
      var length = $scope.resources.length;
      selectedResourceIndex = (((selectedResourceIndex - 1) % length) + length) % length;
      $scope.selected.resource = $scope.resources[selectedResourceIndex];
    };

    /**
     * Go to the next resource.
     */
    $scope.nextResource = function nextResource() {
      var length = $scope.resources.length;
      selectedResourceIndex = (((selectedResourceIndex + 1) % length) + length) % length;
      $scope.selected.resource = $scope.resources[selectedResourceIndex];
    };

    /**
     * Show/hide date picker.
     */
    $scope.toggleDate = function toggleDate() {
      $scope.pickDate = !$scope.pickDate;
      $scope.pickResource = false;
      $scope.pickTime = false;
    };

    /**
     * Show/hide time picker.
     */
    $scope.toggleTime = function toggleTime() {
      $scope.pickTime = !$scope.pickTime;
      $scope.pickDate = false;
      $scope.pickResource = false;
    };

    /**
     * Show/hide resource picker.
     */
    $scope.toggleResource = function toggleResource() {
      $scope.pickResource = !$scope.pickResource;
      $scope.pickDate = false;
      $scope.pickTime = false;
    };

    /**
     * Get current date/time selections as unix timestamps.
     *
     * Build timestamps to send to the server based on the date picker and time picker selector. The first issue is
     * that the time picker returns date information for the time selected today, while we want to combine the date
     * select and only get the time selected (without the date from the time picker).
     *
     * @returns {{from: number, to: number}}
     */
    function getSelecteDateTimesAsUnixTimestamp() {
      var from = new Date($scope.selected.date.toDate().getTime());
      var fromTime = $scope.selected.time.start;
      from.setHours(fromTime.getHours(), fromTime.getMinutes(), 0, 0);

      var to = new Date($scope.selected.date.toDate().getTime());
      var toTime = $scope.selected.time.end;
      to.setHours(toTime.getHours(), toTime.getMinutes(), 0, 0);

      return {
        "from": Math.floor(from.getTime() / 1000),
        "to": Math.floor(to.getTime() / 1000)
      };
    }

    /**
     * Validate the current selection.
     */
    function validateBooking() {
      if (!$scope.selected.resource || !$scope.selected.date || !$scope.selected.time.start) {
        $scope.validating = false;
        return;
      }

      var times = getSelecteDateTimesAsUnixTimestamp();

      var validBooking = true;

      for (var i = 0; i < $scope.bookings.length; i++) {
        var booking = $scope.bookings[i];

        // Check that the selected time is not blocked by an existing booking.
        // Consecutive bookings can stop and start at the same time.
        if ((booking.start <= times.from && times.from < booking.end)
            || (booking.start < times.to && times.to <= booking.end)) {
          // The selected time interval overlaps with this booking.
          validBooking = false;

          // Break for loop as one interval just have to be blocked to make the
          // booking invalid.
          break;
        }
      }

      $scope.validBooking = validBooking;

      if (!validBooking) {
        $scope.showCalendar = true;
      }

      $scope.validating = false;
    }

    /**
     * Get the show on the road.
     */
    init();
  }
]);
