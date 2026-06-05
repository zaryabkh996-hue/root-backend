<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <!-- Header -->
    <tr>
      <td style="padding: 20px; background: #0a1810; border-radius: 8px 8px 0 0;">
        <div style="font-family: Georgia, serif; font-size: 24px; font-weight: 600; color: #c9a14a;">
          OurRoots.Africa
        </div>
        <p style="color: #f3ede0; margin-top: 8px; font-size: 14px;">Akwaaba, {{ $recipientName }}</p>
      </td>
    </tr>

    <!-- Main Content -->
    <tr>
      <td style="padding: 40px; background: #f3ede0;">
        <div style="display: flex; align-items: center; margin-bottom: 24px;">
          <div style="font-size: 32px; margin-right: 12px;">📅</div>
          <h1 style="font-family: Georgia, serif; font-size: 28px; color: #0a1810; margin: 0; font-weight: 300;">
            Booking Confirmed
          </h1>
        </div>
        
        <p style="color: #6b6560; font-size: 16px; line-height: 1.6; margin-bottom: 32px;">
          Your booking has been successfully created! Here are the details.
        </p>

        <!-- Booking Details Card -->
        <div style="background: white; border-radius: 8px; padding: 24px; margin-bottom: 32px; border-left: 4px solid #c9a14a;">
          <div style="margin-bottom: 20px;">
            <p style="color: #8a7f72; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px 0; font-weight: 600;">
              Booking Date & Time
            </p>
            <p style="color: #0a1810; font-size: 18px; font-weight: 600; margin: 0;">
              {{ $bookingDate }} at {{ $bookingTime }}
            </p>
          </div>

          <hr style="border: none; border-top: 1px solid #e5ddd0; margin: 20px 0;">

          <div style="margin-bottom: 20px;">
            <p style="color: #8a7f72; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px 0; font-weight: 600;">
              With
            </p>
            <p style="color: #0a1810; font-size: 16px; font-weight: 600; margin: 0;">
              @if($recipientRole === 'customer')
                {{ $custodianName }}
              @else
                {{ $customerName }}
              @endif
            </p>
          </div>

          @if($bookingMessage)
            <hr style="border: none; border-top: 1px solid #e5ddd0; margin: 20px 0;">
            <div>
              <p style="color: #8a7f72; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px 0; font-weight: 600;">
                Message
              </p>
              <p style="color: #6b6560; font-size: 14px; line-height: 1.6; margin: 0; font-style: italic;">
                {{ $bookingMessage }}
              </p>
            </div>
          @endif
        </div>

        <!-- Action for Customers -->
        @if($recipientRole === 'customer')
          <div style="background: #f0f0f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <p style="color: #6b6560; font-size: 14px; line-height: 1.6; margin: 0;">
              <strong>What's next?</strong><br>
              Your custodian will confirm or respond to your booking. You can track the status in your OurRoots.Africa dashboard.
            </p>
          </div>
        @endif

        <!-- Action for Custodians -->
        @if($recipientRole === 'custodian')
          <div style="text-align: center; margin: 32px 0;">
            <a href="{{ config('app.frontend_url') }}/custodian/bookings" style="display: inline-block; background: #c9a14a; color: #0a1810; padding: 14px 32px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 16px;">
              View & Respond to Booking
            </a>
          </div>
          <p style="text-align: center; color: #8a7f72; font-size: 13px; margin-bottom: 24px;">
            Review the booking details and confirm or reschedule
          </p>
        @endif

        <!-- Important Notice -->
        <div style="background: #fff5f5; border-left: 4px solid #dc2626; padding: 16px; border-radius: 4px;">
          <p style="color: #7f1d1d; font-size: 13px; margin: 0;">
            <strong>📌 Important:</strong> Please keep this email for your records. You can view full booking details in your OurRoots.Africa account.
          </p>
        </div>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding: 20px; background: #0a1810; border-radius: 0 0 8px 8px; text-align: center;">
        <p style="color: #f3ede0; color: rgba(243, 237, 224, 0.6); font-size: 12px; margin: 0;">
          © 2026 OurRoots.Africa. All rights reserved.<br>
          This is an automated message. Please do not reply to this email.
        </p>
      </td>
    </tr>
  </table>
</div>
