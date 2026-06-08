<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\BookingEmail;
use App\Helpers\MailjetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
                return response()->json(['error' => 'Unauthenticated'], 401);
            }



            $validator = Validator::make($request->all(), [
                'custodian_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where('role', 'custodian'),
                ],
                'booking_date' => 'required|date',
                'booking_time' => 'required|string',
                'message' => 'nullable|string|max:500',
                'session_type' => 'nullable|string|max:255',
                'session_duration' => 'nullable|integer|min:1',
                'platform_link' => 'nullable|string|max:255',
                'amount_charged_usd' => 'nullable|numeric|min:0',
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
                'session_type' => $request->session_type,
                'session_duration' => $request->session_duration,
                'platform_link' => $request->platform_link,
                'amount_charged_usd' => $request->amount_charged_usd,
                'status' => 'pending',
            ]);



            // Send email to customer
            try {

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


            } catch (\Exception $emailError) {
            }

            // Send email to custodian
            try {

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


            } catch (\Exception $emailError) {
            }

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Booking created successfully',
            ], 201);
        } catch (\Exception $e) {
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
            return response()->json(['error' => 'Failed to fetch bookings'], 500);
        }
    }

    /**
     * Get bookings for custodian (their calendar)
     */
    public function getCustodianBookings(Request $request)
    {
       
         if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        try {
          
            $bookings = Booking::where('custodian_id', $request->user()->id)
                ->with('user')
                ->orderBy('booking_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);
        } catch (\Exception $e) {
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



            // Send email to customer
            try {

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


            } catch (\Exception $emailError) {
            }

            // Send email to custodian
            try {

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


            } catch (\Exception $emailError) {
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
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
                'status' => 'nullable|string|in:pending,confirmed,completed,cancelled',
                'session_type' => 'nullable|string|max:255',
                'session_duration' => 'nullable|integer|min:1',
                'platform_link' => 'nullable|string|max:255',
                'amount_charged_usd' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $updateData = array_filter([
                'status' => $request->status,
                'session_type' => $request->session_type,
                'session_duration' => $request->session_duration,
                'platform_link' => $request->platform_link,
                'amount_charged_usd' => $request->amount_charged_usd,
            ], function ($value) {
                return $value !== null;
            });

            $booking->update($updateData);



            // Send email to customer
            try {

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


            } catch (\Exception $emailError) {
            }

            // Send email to custodian
            try {

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


            } catch (\Exception $emailError) {
            }

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Booking updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update booking'], 500);
        }
    }
}
