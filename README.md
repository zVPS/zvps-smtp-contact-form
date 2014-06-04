PHP SMTP Auth Contact Form
======================

If your hosting provider requires all requests to send emails to authenticate with a valid email account this example may work for you. Most ZPanel hosting providers have this restriction in place to help prevent spamming on their services.

## Installation
* Put the *inc/* directory and *contact.php* script in your project.
* Signup for recaptcha public and private keys https://www.google.com/recaptcha/
* Alter the configurable options inside *inc/mailer.php*

## Security Considerations
It would be advisable to review this code and the impact it could have before using it. We will NOT be held responsable for any negative effects deploying this code to your website may have.

It would be advisable to move the *inc/* directory outside of the web directory and alter the *require_once* statements to follow the new directory structure.

## Improvements
We are accepting pull requests, if you find any bugs or issues please submit a pull request with a suggested fix. We will review, and all being well accept each request.
