<?php

namespace HiEvents\Mail\Cashless;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\CashlessTransactionDomainObject;
use HiEvents\DomainObjects\CashlessWalletDomainObject;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\Helper\Currency;
use HiEvents\Helper\Url;
use HiEvents\Mail\BaseMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

/**
 * @uses /backend/resources/views/emails/cashless/topup-confirmation.blade.php
 */
class CashlessTopupConfirmationMail extends BaseMail
{
    public function __construct(
        private readonly CashlessWalletDomainObject $wallet,
        private readonly CashlessTransactionDomainObject $transaction,
        private readonly AttendeeDomainObject $attendee,
        private readonly EventDomainObject $event,
        private readonly EventSettingDomainObject $eventSettings,
        private readonly OrganizerDomainObject $organizer,
    ) {
        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: $this->eventSettings->getSupportEmail(),
            subject: __('💳 Your cashless balance for :event', [
                'event' => Str::limit($this->event->getTitle(), 50),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cashless.topup-confirmation',
            with: [
                'event' => $this->event,
                'eventSettings' => $this->eventSettings,
                'organizer' => $this->organizer,
                'attendee' => $this->attendee,
                'toppedUpAmount' => Currency::format(
                    abs($this->transaction->getAmount()),
                    $this->wallet->getCurrency(),
                ),
                'newBalance' => Currency::format(
                    $this->transaction->getBalanceAfter(),
                    $this->wallet->getCurrency(),
                ),
                'walletUrl' => sprintf(
                    Url::getFrontEndUrlFromConfig(Url::CASHLESS_WALLET),
                    $this->event->getId(),
                    $this->attendee->getShortId(),
                ),
            ]
        );
    }
}
