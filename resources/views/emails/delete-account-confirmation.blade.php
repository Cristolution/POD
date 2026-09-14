<x-mail::message>
# {{ __('account_deletion_mail_heading') }}

{{ __('account_deletion_mail_greeting', ['name' => $name]) }}

{{ __('account_deletion_mail_body') }}

<x-mail::button :url="$confirmationUrl">
{{ __('account_deletion_mail_button') }}
</x-mail::button>

{{ __('account_deletion_mail_expiry_notice') }}

{{ __('account_deletion_mail_footer') }}
</x-mail::message>