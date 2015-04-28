angular.module('kobaApp').controller("CalendarController", ['$scope', '$window',
  function ($scope, $window) {
    // Open up for translations.
    $scope.t = function(str) {
      return $window.Drupal.t(str);
    };

    // Get start of today
    var today = new Date();
    today = new Date(today.setHours(0,0,0,0));

    // Defaults: Start of today
    $scope.selected = {
      "date": today,
      "time": {
        "start": "08:00",
        "end": "09:00"
      }
    };
  }
]);