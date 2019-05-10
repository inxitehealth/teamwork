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
        return $this->from($this->data->email)
            ->subject($this->data->subject)
            ->attach(Storage::disk('send_report')->path($this->data->attachment))
            ->view('mails.sendreport');
    }
}
