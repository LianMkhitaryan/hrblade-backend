<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $hidden = ['default'];

    public function getEmailContent(Response $response, Company $company)
    {
        $this->email = str_replace('{{interview}}', $response->job->name, $this->email);

        $this->email = str_replace('{{candidate_name}}', $response->full, $this->email);
        $this->email = str_replace('{{company_name}}', $company->name, $this->email);
        $this->email = str_replace('{{company_phone}}', $company->phone, $this->email);
        $this->email = str_replace('{{company_website}}', $company->website, $this->email);
        if ($response->link) {
            $link = '<a href="' . env("APP_PAGE") . "i/" . $response->link->hash . '">' . env("APP_PAGE") . "i/" . $response->link->hash . '</a>';
        } else {
            $link = ' ';
        }
        $this->email = str_replace('{{interview_link}}', $link, $this->email);

        $btnInvite = '<a href="' . env("APP_PAGE") . "i/" . $response->link->hash . '" style=" background-attachment: scroll;
                    display: block;
                    width: 100%;
                    max-width: 340px;
                    height: 55px;
                    margin-top: 0;
                    margin-bottom: 0;
                    margin-right: auto;
                    margin-left: auto;
                    border-radius: 5px;
                    font-weight: 600;
                    line-height: 55px;
                    font-size: 16px;
                    text-decoration: none;
                    font-family: Helvetica, sans-serif;
                    text-align: center;
                    color: #ffffff;
                    background-color: #ffab42;
                    background-image: none;
                    background-repeat: repeat;
                    background-position: top left;
                    cursor: pointer;
                    box-sizing: border-box;">
                          ' . __('messages.run_interview') . '
                        </a>';
        $this->email = str_replace('{{interview_invite_btn}}', $btnInvite, $this->email);

        $btnShow = '<a href="' . env("APP_PAGE") . "s/" . $response->getOriginal('hash') . '" style=" background-attachment: scroll;
                    display: block;
                    width: 100%;
                    max-width: 340px;
                    height: 55px;
                    margin-top: 0;
                    margin-bottom: 0;
                    margin-right: auto;
                    margin-left: auto;
                    border-radius: 5px;
                    font-weight: 600;
                    line-height: 55px;
                    font-size: 16px;
                    text-decoration: none;
                    font-family: Helvetica, sans-serif;
                    text-align: center;
                    color: #ffffff;
                    background-color: #ffab42;
                    background-image: none;
                    background-repeat: repeat;
                    background-position: top left;
                    cursor: pointer;
                    box-sizing: border-box;">
                          ' . __('messages.view_response') . '
                        </a>';
        $this->email = str_replace('{{response_show_btn}}', $btnShow, $this->email);

        return $this->email;
    }

    public function getSmsContent(Response $response, Company $company)
    {
        $this->sms = str_replace('{{interview}}', $response->job->name, $this->sms);
        if ($response->link) {
            $link = env("APP_PAGE") . "i/" . $response->link->hash;
        } else {
            $link = ' ';
        }
        $this->sms = str_replace('{{interview_link}}', $link, $this->sms);
        $this->sms = str_replace('{{candidate_name}}', $response->full, $this->sms);
        $this->sms = str_replace('{{company_name}}', $company->name, $this->sms);
        $this->sms = str_replace('{{company_phone}}', $company->phone, $this->sms);
        $this->sms = str_replace('{{company_website}}', $company->website, $this->sms);

        return $this->sms;
    }

    public function getView()
    {
        switch ($this->type) {
            case 'INVITE':
                $view = 'emails.new.invite_interview';
                break;
            case 'ACCEPT':
                $view = 'emails.new.invite_interview';
                break;
            case 'REJECT':
                $view = 'emails.new.invite_interview';
                break;
            case 'RESPONSE':
                $view = 'emails.new.invite_interview';
                break;
            default:
                $view = 'emails.new.invite_interview';
        }

        return $view;
    }

    public function getEmailTitle(Response $response, Company $company)
    {
        $this->email_title = str_replace('{{interview}}', $response->job->name, $this->email_title);
        if ($response->link) {
            $link = env("APP_PAGE") . "i/" . $response->link->hash;
        } else {
            $link = ' ';
        }
        $this->email_title = str_replace('{{interview_link}}', $link, $this->email_title);
        $this->email_title = str_replace('{{candidate_name}}', $response->full, $this->email_title);
        $this->email_title = str_replace('{{company_name}}', $company->name, $this->email_title);
        $this->email_title = str_replace('{{company_phone}}', $company->phone, $this->email_title);
        $this->email_title = str_replace('{{company_website}}', $company->website, $this->email_title);

        return $this->email_title;
    }
}
