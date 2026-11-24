<x-mail::message>
# You're invited to Escalora

Hi {{ $agentName }},

**{{ $inviterName }}** invited you to join **{{ $companyName }}** as a support agent in the **{{ $workspaceName }}** workspace.

Click the button below to set your password and activate your account. This link expires in 3 days.

<x-mail::button :url="$acceptUrl">
Activate account
</x-mail::button>

If you were not expecting this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
