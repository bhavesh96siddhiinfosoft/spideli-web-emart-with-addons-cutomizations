<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Mail\SetEmailData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendEmailController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
    }

    function send(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required'
        ]);
        $data = array(
            'name' => $request->name,
            'message' => $request->message
        );
        Mail::to(env('MAIL_TO_ADDRESS'))->send(new SendMail($data, $request->email));
        return back()->with('success_contact', 'Thanks for contacting us!');
    }

    function index()
    {
        return view('send_email');
    }

    /**
     * Sends one of the admin panel's email templates.
     *
     * `to_admin` sends it to the address in MAIL_TO_ADDRESS instead of to a
     * recipient list. The address stays SERVER-SIDE: putting it in the page
     * for the browser to post back would publish it in the page source and
     * let anyone address mail to it.
     *
     * With no admin address configured, the admin copy is skipped rather than
     * failing - the customer's own email, and whatever the customer was doing,
     * must not depend on that setting being filled in.
     */
    function sendMail(Request $request)
    {
        $data = $request->all();
        $subject = $data['subject'] ?? '';
        $message = isset($data['message']) ? base64_decode($data['message']) : '';

        if (!empty($data['to_admin'])) {
            $adminAddress = env('MAIL_TO_ADDRESS');

            if (empty($adminAddress)) {
                return "no admin address configured";
            }

            try {
                Mail::to($adminAddress)->send(new SetEmailData($subject, $message));
                return "email sent successfully!";
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('SendEmailController to_admin error: ' . $e->getMessage());
                return "email sending failed: " . $e->getMessage();
            }
        }

        $recipients = $data['recipients'] ?? [];
        if (!empty($recipients)) {
            try {
                Mail::to($recipients)->send(new SetEmailData($subject, $message));
                return "email sent successfully!";
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('SendEmailController error: ' . $e->getMessage());
                return "email sending failed: " . $e->getMessage();
            }
        }
        return "no recipients";
    }
}

?>