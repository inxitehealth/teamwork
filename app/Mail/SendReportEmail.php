<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class SendReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The data object instance.
     *
     * @var Data
     */
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->from($this->data->email)
            ->subject($this->data->subject)
            ->view('mails.sendreport');

        // $attachments is an array with file paths of attachments
        foreach ($this->data->attachment as $filePath) {
            $email->attach(Storage::disk('send_report')->path($filePath));
        }
        return $email;
    }
}
