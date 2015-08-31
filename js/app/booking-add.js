/**
 *
 * Show hide Association
 *
 */

(function($) {
  // Show / Hide association
  function toggle_association() {
    $('input:radio[name="booking_type"]').change(function() {
      if ($(this).is(':checked') && $(this).val() == 'private') {
        $('.js-booking-type-toggle').hide();
      } else {
        $('.js-booking-type-toggle').show();
      }
    });
  }

  // Start the show.
  $(document).ready(function () {
    $('.js-booking-type-toggle').hide();
    toggle_association();
  });

})(jQuery);