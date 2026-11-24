<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $agent,
        public string $plainToken,
        public User $invitedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to join Escalora',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.agent-invitation',
            with: [
                'acceptUrl' => route('agent.invitation.show', ['token' => $this->plainToken]),
                'workspaceName' => $this->agent->workspace?->name,
                'companyName' => $this->agent->company?->name,
                'inviterName' => $this->invitedBy->name,
                'agentName' => $this->agent->name,
            ],
        );
    }
}
