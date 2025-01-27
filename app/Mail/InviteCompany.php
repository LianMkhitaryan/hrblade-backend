<?php

namespace App\Mail;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InviteCompany extends Mailable
{
    use Queueable, SerializesModels;

    public $invite;
    public $companies;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invite $invite, $companies)
    {
        $this->invite = $invite;
        $this->companies = $companies;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.invites.company')->subject(__('messages.invite_to_company_subject'));
    }
}
