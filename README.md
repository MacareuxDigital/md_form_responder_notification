# Concrete CMS Package: Macareux Form Responder Notification

A Concrete CMS Package to make it enable to send responders a copy of their reponses.

## Getting Started

Go to Dashboard > System & Settings > Email > Form Response page.
You can set the email address to send from, reply to, and email template for each form.

You can also use PHP templates to customize the email content.
Email templates should be like below and placed in `application/mail` directory.

```php
<?php
// application/mail/contact_form_response.php

defined('C5_EXECUTE') or die("Access Denied.");

$subject = t('%s Form Submission', $formName);

$body = t("Dear %s,

Thank you for writing to us.
Here is what you have submitted:

Topic: %s
Message: %s

Thank you for your patience!", $your_name, $topic, $message);
```

Attribute values are automatically mapped as `$attribute_key_handle`.

### Config file

You can set config file at `application/config/md_form_responder_notification/forms.php` like this:

```php
<?php

return [
    // Please use the express entity handle as key
    'contact' => [
        'from' => 'no-reply@example.com',
        'reply_to' => 'contact@example.com',
        'template' => 'contact_form_response', // Email template
    ],
    'recruit' => [
        // If you don't set from or reply_to, 'concrete.email.form_block.address' value will be used instead.
        'template' => 'recruit_form_response',
    ],
];
```

If you put config files, you can't update the settings from the dashboard.

## Version 8

This package is for Concrete CMS 9.0 or later.
If you are using Concrete CMS 8, please use v8 branch.

## License

MIT License
