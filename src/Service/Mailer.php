<?php
/**
 * @file
 * Contains services to communicate with the KOBA proxy.
 */

namespace Drupal\koba_booking\Service;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\koba_booking\BookingInterface;

class Mailer {

  protected $mailManager;

  /**
   * Default construct.
   *
   * @param $mailManager
   *   Mail manager service to send mail with.
   */
  public function __construct($mailManager) {
    $this->mailManager = $mailManager;
  }

  /**
   * Helper function to send mail based on a booking.
   *
   * @param string $type
   *   The type of the mail (request, accpeted, etc.)
   * @param \Drupal\koba_booking\BookingInterface $booking
   *   Booking object that this mail is about.
   */
  public function send($type, BookingInterface $booking = NULL) {
    // Get to mail address.
    $to = $booking->booking_email->value;

    // Try to get and from address from the site configuration.
    $site_config = \Drupal::config('system.site');
    $from = $site_config->get('mail');
    if (empty($from)) {
      $from = ini_get('sendmail_from');
    }

    // Build render array for the mail body.
    $subject = 'Unknown mail type';
    $content = array();
    $config = \Drupal::config('koba_booking.settings');
    switch ($type) {
      case 'request':
        $subject = $config->get('koba_email.email_pending_title');

        // Build render array.
        $content = array(
          '#theme' => 'booking_mail_request',
          '#message' => $config->get('koba_email.email_pending_body'),
        );
        break;

      case 'accepted':
        $subject = $config->get('koba_email.accepted_email_title');

        // Build render array.
        $content = array(
          '#theme' => 'booking_mail_accepted',
          '#message' => $config->get('koba_email.accepted_email_body'),
        );
        break;

      case 'rejected':
        $subject = $config->get('koba_email.rejected_email_title');

        // Build render array.
        $content = array(
          '#theme' => 'booking_mail_accepted',
          '#message' => $config->get('koba_email.rejected_email_body'),
        );
        break;

      case 'cancelled':
        $subject = $config->get('koba_email.cancelled_email_title');

        // Build render array.
        $content = array(
          '#theme' => 'booking_mail_accepted',
          '#message' => $config->get('koba_email.cancelled_email_body'),
        );
        break;
    }

    // Extend content with booking information.
    if (!is_null($booking)) {
      $content += $this->generate_booking_array($booking);
    }

    // Render the body content for the mail.
    $body = render($content);

    // Send the mail.
    $this->mailer($to, $from, $subject, $body);
  }


  /**
   * Build render array with booking information.
   *
   * @param \Drupal\koba_booking\BookingInterface $booking
   *   Booking object to generate array for.
   *
   * @return array
   *   Array with booking information.
   */
  protected function generate_booking_array(BookingInterface $booking) {
    // Load room for the booking.
    $room = $booking->getRoomEntity();

    $generator = \Drupal::urlGenerator();
    $url = $generator->generateFromRoute('koba_booking.receipt', array(
      'hash' => $booking->booking_hash->value,
    ), array(
      'absolute' => TRUE,
    ));

    return array(
      '#booking' => array(
        'id' => $booking->id->value,
        'title' => SafeMarkup::checkPlain($booking->name->value),
        'date' => format_date($booking->booking_from_date->value, 'dokk1_booking_dato'),
        'time' => format_date($booking->booking_from_date->value, 'dokk1_booking_time') . ' - ' . format_date($booking->booking_to_date->value, 'dokk1_booking_time'),
        'room' => array(
          'title' => $room->title->value,
          'price' => $room->field_price->value,
          'url' => Url::fromUri($room->url('canonical', array('absolute' => TRUE))),
        ),
        'name' => SafeMarkup::checkPlain($booking->booking_name->value),
        'mail' => SafeMarkup::checkPlain($booking->booking_email->value),
        'phone' => SafeMarkup::checkPlain($booking->booking_phone->value),
        'type' => SafeMarkup::checkPlain($booking->booking_usage->value),
        'message' => SafeMarkup::checkPlain($booking->booking_message->value),
        'url' => $url,
      ),
    );
  }

  /**
   * Send HTML mails.
   *
   * @TODO: This is not the Drupal way to send mail, but rather a hack to send
   *        HTML mails. Drupal MailManger service hardcode plain/text as content
   *        type, so HTML is not supported.
   *
   *        When the SwiftMailer module have been ported to D8... USE IT.
   *
   * @param $to
   *   Mail address to send mail to.
   * @param $from
   *   The sender of the mail.
   * @param $subject
   *   The mails subject.
   * @param $body
   *   The HTML body content to send.
   * @param string $name
   *   The name of the sender. Defaults to 'Dokk1'.
   */
  protected function mailer($to, $from, $subject, $body, $name = 'Dokk1') {
    // Get hold of the RAW mailer client.
    $key = Crypt::randomBytesBase64();
    $mailer = $this->mailManager->getInstance(array('module' => 'koba_booking', 'key' => $key));

    // Build mail configuration and set the type to HTML.
    $params = array(
      'headers' => array(
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Return-Path' => $from,
        'Replay-to' => $from,
        'Sender' => $from,
        'From' => $name . ' <' . $from . '>',
      ),
      'to' => $to,
      'body' => $body,
      'subject' => $subject,
    );

    // Send the mail.
    $mailer->mail($params);
  }
}
