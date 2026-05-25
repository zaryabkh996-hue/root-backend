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
          <div style="font-size: 32px; margin-right: 12px;">❌</div>
          <h1 style="font-family: Georgia, serif; font-size: 28px; color: #dc2626; margin: 0; font-weight: 300;">
            Booking Cancelled
          </h1>
        </div>
        
        <p style="color: #6b6560; font-size: 16px; line-height: 1.6; margin-bottom: 32px;">
          This booking has been cancelled. Here are the details for your records.
        </p>

        <!-- Booking Details Card -->
        <div style="background: white; border-radius: 8px; padding: 24px; margin-bottom: 32px; border-left: 4px solid #dc2626;">
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

          <hr style="border: none; border-top: 1px solid #e5ddd0; margin: 20px 0;">

          <div>
            <p style="color: #8a7f72; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px 0; font-weight: 600;">
              Status
            </p>
            <p style="color: #dc2626; font-size: 16px; font-weight: 600; margin: 0;">
              Cancelled
            </p>
          </div>
        </div>

        <!-- Message to Customer -->
        @if($recipientRole === 'customer')
          <div style="background: #fef3f2; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <p style="color: #7f1d1d; font-size: 14px; line-height: 1.6; margin: 0;">
              <strong>What's next?</strong><br>
              If you'd like to reschedule, please visit your OurRoots.Africa dashboard to create a new booking or contact your custodian directly.
            </p>
          </div>
        @endif

        <!-- Message to Custodian -->
        @if($recipientRole === 'custodian')
          <div style="background: #fef3f2; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <p style="color: #7f1d1d; font-size: 14px; line-height: 1.6; margin: 0;">
              <strong>Note:</strong> This booking has been cancelled and removed from your calendar. The customer has been notified.
            </p>
          </div>
        @endif

        <!-- CTA Button -->
        <div style="text-align: center;">
          <a href="https://root-frontend-production.up.railway.app/bookings" style="display: inline-block; background: #c9a14a; color: #0a1810; padding: 14px 32px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 16px;">
            View Your Bookings
          </a>
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
