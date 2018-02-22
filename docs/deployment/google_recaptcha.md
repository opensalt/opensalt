# How to get Secret Keys for Google reCAPTCHA v2

Opensalt uses Google Invisible reCAPTCHA for prevent DOS attacks. In order to make it work you need to set the following environment variables:
```
GOOGLE_CAPTCHA_SITE_KEY
GOOGLE_CAPTCHA_SECRET_KEY
```

To get the keys you have to follow this steps:

- Log into your Google account.
- Go to https://www.google.com/recaptcha
- On the reCAPTCHA page click on **Get reCAPTCHA** button.
- Choose **Invisible reCAPTCHA** from **Register a new site** box.
- Type the domain you're using for OpenSALT  in **Domains** box.
- Accept the reCAPTCHA terms of service and click on "Register".
- Now you should see your keys displayed in the page.
