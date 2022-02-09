# Concrete CMS Package: Macareux Form Responder Notification

A Concrete CMS Package to make it enable to send responders a copy of their reponses.

## Getting Started

After install this package, you can send a custom email notification per a form.
You need to add config file at `application/config/md_form_responder_notification/forms.php` like this:

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

Then, please add email templates you defined in the config file like this:

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

## ToDo

Dashboard page

## Lincense

MIT License
