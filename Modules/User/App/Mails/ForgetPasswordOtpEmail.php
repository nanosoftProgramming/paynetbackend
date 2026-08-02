<?php

namespace Modules\User\App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgetPasswordOtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    // public $otp;

    /**
     * Create a new message instance.
     *
     * @param mixed $user
     * @param string $otp
     */
    public function __construct($user)
    {
        $this->user = $user;
        // $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // بناء رابط الـ Frontend وتمرير الإيميل والكود معه
        // (تأكد من تغيير http://localhost:5173 برابط موقع الـ Frontend الحقيقي الخاص بك)
        $resetUrl = "http://localhost:5173/reset-password?email=" . urlencode($this->user->email) ;

return $this->view('emails.ForgetPasswordOtpEmail')
            ->subject('Password Reset OTP')
            ->with([
                'user' => $this->user,
                // 'otp' => $this->otp,
                'resetUrl' => $resetUrl, // تمرير الرابط لقالب العرض
            ]);
    }
}