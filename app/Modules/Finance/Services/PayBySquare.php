<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Modules\Finance\Entities\Payment;
use Rikudou\Iban\Iban\IBAN;
use rikudou\SkQrPayment\QrPayment;

/**
 * Renders a PAY by square QR code (the Slovak bank standard) as inline SVG.
 * Requires the `xz` binary on the server; returns null when it is missing so
 * the UI can fall back to copyable payment details.
 */
final class PayBySquare
{
    public function svg(Payment $payment): ?string
    {
        $string = $this->payload($payment);
        if ($string === null) {
            return null;
        }

        $options = new QROptions([
            'outputInterface'      => QRMarkupSVG::class,
            'outputBase64'         => false,
            'svgAddXmlHeader'      => false,
            'eccLevel'             => EccLevel::M,
            'addQuietzone'         => true,
            'quietzoneSize'        => 2,
            'svgUseFillAttributes' => false,
        ]);

        return (new QRCode($options))->render($string);
    }

    public function payload(Payment $payment): ?string
    {
        if ($payment->iban === null || $payment->iban === '' || (float) $payment->amount <= 0) {
            return null;
        }

        try {
            $qr = new QrPayment(new IBAN($payment->iban));
            $qr->setAmount((float) $payment->amount)
                ->setCurrency($payment->currency ?: 'EUR')
                ->setDueDate($payment->due_at->toDateTime())
                ->setComment(mb_substr($payment->title, 0, 140));
            if ($payment->variable_symbol) {
                $qr->setVariableSymbol($payment->variable_symbol);
            }
            if ($payment->specific_symbol) {
                $qr->setSpecificSymbol($payment->specific_symbol);
            }
            if ($payment->constant_symbol) {
                $qr->setConstantSymbol($payment->constant_symbol);
            }
            if ($payment->payee) {
                $qr->setPayeeName(mb_substr($payment->payee, 0, 70));
            }

            return $qr->getQrString();
        } catch (\Throwable $e) {
            log_message('warning', 'PAY by square failed: {error}', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
