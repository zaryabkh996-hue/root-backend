<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\BookingEmail;
use App\Helpers\MailjetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                Log::warning('Unauthenticated access attempt to create booking');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            Log::info('BookingController::store called by user ' . $request->user()->id);

            $validator = Validator::make($request->all(), [
                'custodian_id' => 'required|integer|exists:users,id',
                'booking_date' => 'required|date',
                'booking_time' => 'required|string',
                'message' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Create the booking
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'custodian_id' => $request->custodian_id,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'message' => $request->message,
                'status' => 'pending',
            ]);

            Log::info('Booking created: ' . $booking->id);

            // Send email to customer
            try {
                Log::info('📧 [BOOKING] Sending confirmation email to customer', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email
                ]);

                $customerEmail = new BookingEmail(
                    $booking,
                    'created',
                    $booking->user->email,
                    $booking->user->name,
                    'customer'
                );
                $customerHtmlContent = $customerEmail->render();

                MailjetHelper::sendEmail(
                    $booking->user->email,
                    'New Booking Confirmation - OurRoots.Africa',
                    $customerHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Customer email sent successfully', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send customer email', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            // Send email to custodian
            try {
                Log::info('📧 [BOOKING] Sending notification email to custodian', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email
                ]);

                $custodianEmail = new BookingEmail(
                    $booking,
                    'created',
                    $booking->custodian->email,
                    $booking->custodian->name,
                    'custodian'
                );
                $custodianHtmlContent = $custodianEmail->render();

                MailjetHelper::sendEmail(
                    $booking->custodian->email,
                    'New Booking Request - OurRoots.Africa',
                    $custodianHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Custodian email sent successfully', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send custodian email', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Booking created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('BookingController::store - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get bookings for authenticated user
     */
    public function getUserBookings(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $bookings = Booking::where('user_id', $request->user()->id)
                ->with('custodian')
                ->orderBy('booking_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);
        } catch (\Exception $e) {
            Log::error('BookingController::getUserBookings - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch bookings'], 500);
        }
    }

    /**
     * Get bookings for custodian (their calendar)
     */
    public function getCustodianBookings(Request $request)
    {
       
         if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to custodian bookings');
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        try {
          
            $bookings = Booking::where('custodian_id', $request->user()->id)
                ->with('user')
                ->orderBy('booking_date', 'asc')
                ->get();

                Log::info('BookingController::getCustodianBookings - Fetched ' . $bookings->count() . ' bookings for custodian ' . $request->user()->id);
            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);
        } catch (\Exception $e) {
            Log::error('BookingController::getCustodianBookings - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch bookings'], 500);
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id)
    {
        try {
            if (!$request->user()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $booking = Booking::findOrFail($id);

            // Check if user owns the booking or is the custodian
            if ($booking->user_id !== $request->user()->id && $booking->custodian_id !== $request->user()->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $booking->update(['status' => 'cancelled']);

            Log::info('Booking cancelled: ' . $id);

            // Send email to customer
            try {
                Log::info('📧 [BOOKING] Sending cancellation email to customer', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email
                ]);

                $customerEmail = new BookingEmail(
                    $booking,
                    'cancelled',
                    $booking->user->email,
                    $booking->user->name,
                    'customer'
                );
                $customerHtmlContent = $customerEmail->render();

                MailjetHelper::sendEmail(
                    $booking->user->email,
                    'Booking Cancelled - OurRoots.Africa',
                    $customerHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Customer cancellation email sent successfully', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send customer cancellation email', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            // Send email to custodian
            try {
                Log::info('📧 [BOOKING] Sending cancellation email to custodian', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email
                ]);

                $custodianEmail = new BookingEmail(
                    $booking,
                    'cancelled',
                    $booking->custodian->email,
                    $booking->custodian->name,
                    'custodian'
                );
                $custodianHtmlContent = $custodianEmail->render();

                MailjetHelper::sendEmail(
                    $booking->custodian->email,
                    'Booking Cancelled - OurRoots.Africa',
                    $custodianHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Custodian cancellation email sent successfully', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send custodian cancellation email', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('BookingController::cancel - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel booking'], 500);
        }
    }

    /**
     * Update booking status
     */
    public function update(Request $request, $id)
    {
        try {
            if (!$request->user()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $booking = Booking::findOrFail($id);

            // Only custodian can update the booking status
            if ($booking->custodian_id !== $request->user()->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,confirmed,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $booking->update(['status' => $request->status]);

            Log::info('Booking ' . $id . ' status updated to: ' . $request->status);

            // Send email to customer
            try {
                Log::info('📧 [BOOKING] Sending update email to customer', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email,
                    'new_status' => $request->status
                ]);

                $customerEmail = new BookingEmail(
                    $booking,
                    'updated',
                    $booking->user->email,
                    $booking->user->name,
                    'customer'
                );
                $customerHtmlContent = $customerEmail->render();

                MailjetHelper::sendEmail(
                    $booking->user->email,
                    'Booking Updated - OurRoots.Africa',
                    $customerHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Customer update email sent successfully', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email,
                    'new_status' => $request->status
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send customer update email', [
                    'booking_id' => $booking->id,
                    'customer_email' => $booking->user->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            // Send email to custodian
            try {
                Log::info('📧 [BOOKING] Sending update email to custodian', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email,
                    'new_status' => $request->status
                ]);

                $custodianEmail = new BookingEmail(
                    $booking,
                    'updated',
                    $booking->custodian->email,
                    $booking->custodian->name,
                    'custodian'
                );
                $custodianHtmlContent = $custodianEmail->render();

                MailjetHelper::sendEmail(
                    $booking->custodian->email,
                    'Booking Updated - OurRoots.Africa',
                    $custodianHtmlContent,
                    'OurRoots.Africa'
                );

                Log::info('✅ [BOOKING] Custodian update email sent successfully', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email,
                    'new_status' => $request->status
                ]);
            } catch (\Exception $emailError) {
                Log::error('❌ [BOOKING] Failed to send custodian update email', [
                    'booking_id' => $booking->id,
                    'custodian_email' => $booking->custodian->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Booking updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('BookingController::update - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update booking'], 500);
        }
    }
}
