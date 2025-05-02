<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class AdminPharmacistRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pharmacist;
    protected $licenseImage;
    protected $tinImage;
    protected $verificationToken;

    public function __construct(User $pharmacist, $licenseImage, $tinImage, $verificationToken)
    {
        $this->pharmacist = $pharmacist;
        $this->licenseImage = $licenseImage;
        $this->tinImage = $tinImage;
        $this->verificationToken = $verificationToken;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = url('/api/verify-email/' . $this->verificationToken);
        $approveUrl = url('/api/admin/pharmacists/' . $this->pharmacist->id . '/action?status=approved&reason=Documents verified');
        $rejectUrl = url('/api/admin/pharmacists/' . $this->pharmacist->id . '/action?status=rejected&reason=Documents not verified');

        return (new MailMessage)
            ->subject('New Pharmacist Registration - Action Required')
            ->greeting('Hello Admin,')
            ->line('A new pharmacist has registered and requires your approval.')
            ->line('Pharmacist Details:')
            ->line('Name: ' . $this->pharmacist->name)
            ->line('Email: ' . $this->pharmacist->email)
            ->line('License Number: ' . $this->pharmacist->license_number)
            ->line('License Image:')
            ->action('View License', url($this->licenseImage))
            ->line('TIN Image:')
            ->action('View TIN', url($this->tinImage))
            ->line('Please review the documents and take appropriate action:')
            ->action('Verify Email', $verificationUrl)
            ->line('Click the buttons below to approve or reject the registration:')
            ->action('Approve Registration', $approveUrl)
            ->action('Reject Registration', $rejectUrl)
            ->line('Thank you for your attention to this matter.');
    }
} 