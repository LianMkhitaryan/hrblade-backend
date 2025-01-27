<?php

namespace App\Mail;

use App\Models\Link;
use App\Models\Response;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InviteInterwiew extends Mailable
{
    use Queueable, SerializesModels;

    public $link, $content, $company, $template;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link, $company, $template)
    {
        $this->link = $link;
        $this->company = $company;
        $this->template = $template;
        $this->content = $template->getEmailContent($link->response, $company);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view($this->template->getView())->subject($this->template->getEmailTitle($this->link->response, $this->company));
    }
}
