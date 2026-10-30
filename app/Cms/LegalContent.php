<?php

namespace App\Cms;

/**
 * Starting text of the FAQ and policy pages (Markdown). These describe how
 * the platform works today; the terms, privacy and cookie policies should
 * be reviewed by a legal adviser before launch.
 */
class LegalContent
{
    public static function faq(): string
    {
        return <<<'MD'
## Booking

**How do I book a stay?**
Search by area and dates on the home page, choose a property, then pick your room, number of guests and meal plan. Enter your details, accept the terms and confirm. You will receive a confirmation email with your booking reference.

**Do I need an account?**
No. You can book as a guest. If you like, you can create an account while booking to see all your bookings in one place.

**Can I book more than one room?**
Yes. Choose the number of rooms when you search. The price shown is the total for all rooms and all nights.

## Prices and payment

**Why are prices shown in rupees or US dollars?**
Sri Lankan properties offer local rates in Sri Lankan rupees (LKR) and rates for international guests in US dollars (USD). You can switch the currency at the top of the page. Local rates may require a Sri Lankan NIC at check-in.

**How can I pay?**
You can pay online by card when you book, or choose to pay at the property. If you pay online, your rooms are held for a short time while you complete the payment.

**Are taxes included?**
The price shown is the total for your stay as set by the property. If a property charges anything extra, such as optional activities, it will tell you directly.

## Changes and cancellations

**How do I cancel?**
Open your booking page from your confirmation email, or use [Find my booking](/booking/find) with your booking reference and email, and choose *Cancel booking*.

**Will I get a refund?**
Each property sets its own cancellation policy, shown before you book and in your confirmation. If you cancel on or before the free-cancellation date, any amount you have paid is refunded. Cancellations after that date, and no-shows, are not refunded.

**Can I change my dates?**
Please contact the property or our team with your booking reference and we will help you.

## For property owners

**How do I list my property?**
[Create an owner account](/signup). Once our team has approved it, you can add your property, room types, rates and photos.

**What does it cost?**
There is no set-up fee. EpickTours charges a commission on each booking paid.

## Still have a question?

Ask our virtual receptionist using the chat button, or [contact us](/contact).
MD;
    }

    public static function terms(): string
    {
        return <<<'MD'
These terms apply when you use the EpickTours website and when you book a stay through it. By using the website or making a booking, you agree to them.

## 1. About EpickTours

EpickTours operates an online platform through which independent hotels, villas, guesthouses and other accommodation providers in Sri Lanka ("properties") offer their rooms to travellers. When you book, your accommodation contract is with the property. EpickTours provides the booking service and, where you pay online, collects the payment on the property's behalf.

## 2. Bookings

- A booking is confirmed when you receive a confirmation with a booking reference. If you choose to pay online, the booking is confirmed once the payment has been received.
- Make sure the details you enter, including names, dates, number of guests and contact details, are correct.
- Properties may ask for identification at check-in. Local (LKR) rates may require a valid Sri Lankan NIC. Guests who cannot show one may be charged the international (USD) rate.

## 3. Prices and payment

- Prices are set by each property and shown for the selected dates, rooms, number of guests and meal plan, in LKR or USD.
- You can pay online by card or, where offered, at the property. Online payments are processed by our payment provider; we do not store your card details.

## 4. Cancellations, no-shows and refunds

- Each property sets a cancellation policy, shown before you book and in your confirmation.
- If you cancel on or before the free-cancellation date, any amount you have paid will be refunded to the original payment method.
- Cancellations after that date, and no-shows, are not refunded.
- Refunds may take several working days to appear, depending on your bank.

## 5. Your responsibilities

You agree to use the website lawfully, to provide accurate information, and to respect the rules of the property where you stay. Properties may refuse guests who cause damage, disturbance or danger.

## 6. Property information

Properties are responsible for the accuracy of their descriptions, photos, rates and availability. We work to keep the information on the website accurate but cannot guarantee that it is always complete or error-free.

## 7. Liability

To the extent permitted by law, EpickTours is not liable for the services provided by properties, or for indirect or consequential loss. Nothing in these terms limits liability that cannot be limited by law.

## 8. Changes to these terms

We may update these terms from time to time. The version published on the website when you make a booking applies to that booking.

## 9. Governing law

These terms are governed by the laws of Sri Lanka.

## 10. Contact

Questions about these terms? [Contact us](/contact).
MD;
    }

    public static function privacy(): string
    {
        return <<<'MD'
This policy explains what personal information EpickTours collects, why, and how we protect it.

## Information we collect

- **Booking details:** your name, email address, phone number, optional address and country, stay dates, rooms and any special requests.
- **Account details:** if you create an account, your name, email address, phone number and a securely hashed password.
- **Payment information:** payment amounts, status and references. Card details are handled by our payment provider and are never stored by EpickTours.
- **Messages:** what you send us through the contact form, and your conversations with our virtual receptionist.
- **Technical information:** your IP address, browser and the cookies described in our [cookie policy](/page/cookies).

## How we use it

- To make, manage and confirm your bookings, and to send you booking emails and invoices.
- To share the details a property needs with the property where you will stay.
- To answer your questions and provide support.
- To recognise returning guests and improve our service.
- To prevent fraud, keep the website secure and meet our legal obligations.

## Who we share it with

- **The property you book**, so it can host you.
- **Our payment provider**, to process online payments and refunds.
- **Our email provider**, to send booking emails.
- **Our AI service provider**, which processes the messages you write to our virtual receptionist so it can answer them. Please do not share card details or passwords in the chat.

We do not sell your personal information.

## How long we keep it

We keep booking and payment records for as long as needed for accounting, tax and legal purposes. Other information is kept only as long as it is useful for the purpose for which it was collected.

## Your rights

You can ask us for a copy of your personal information, ask us to correct it, or ask us to delete information we no longer need to keep. [Contact us](/contact) to make a request.

## Security

We protect your information with encryption in transit, hashed passwords, restricted staff access and audit logs of changes.

## Changes

We may update this policy. The latest version is always published on this page.
MD;
    }

    public static function cookies(): string
    {
        return <<<'MD'
Cookies are small files that a website stores in your browser. EpickTours uses only the cookies needed for the website to work.

| Cookie | Purpose | How long |
|---|---|---|
| Session cookie | Keeps you signed in, remembers your chat with our virtual receptionist and your choices while you browse | Until you close your browser or after a period of inactivity |
| Security (XSRF) cookie | Protects forms against cross-site request forgery | Same as the session |
| Currency cookie | Remembers whether to show you local (LKR) or international (USD) prices | Up to 2 years |

We do not currently use advertising or analytics cookies. If that changes, we will update this page and ask for your consent where required.

You can delete cookies or block them in your browser settings, but parts of the website, such as booking, may not work without them.
MD;
    }
}
