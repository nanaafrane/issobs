<?php

namespace App\Services\Receipts;

/**
 * Pure arithmetic for splitting one payment across several invoices.
 *
 * No database access — it takes what is owed on each invoice and what the
 * cashier typed, and returns exactly how much goes where, plus anything left
 * over (the client's unapplied credit / advance). Keeping it pure makes the
 * rules easy to read and to test.
 *
 * For each invoice line:
 *   settled   = amount_applied + wht + vat7 + deduction
 *   remaining = outstanding - settled            (never allowed below zero)
 *
 * For the receipt:
 *   unapplied = money_received - SUM(amount_applied)   (never below zero)
 */
class ReceiptAllocator
{
    public const TOLERANCE = 0.01;

    /**
     * @param  array<int, array{invoice_id:int, outstanding:float, applied?:float|null, wht?:float|null, vat7?:float|null, deduction?:float|null}>  $lines
     *         In the order money should be applied when amounts are left blank
     *         (oldest invoice first).
     * @param  float  $moneyReceived  cash + momo + cheque + transfer + other
     * @return array{lines: array<int, array>, applied_total: float, unapplied: float, errors: array<int, string>}
     */
    public function plan(array $lines, float $moneyReceived): array
    {
        $errors = [];
        $moneyReceived = $this->money($moneyReceived);

        if ($moneyReceived < 0) {
            $errors[] = 'The amount received cannot be negative.';
        }

        // Money already committed to lines whose amount was typed in.
        $explicit = 0.0;
        foreach ($lines as $line) {
            if (isset($line['applied']) && $line['applied'] !== null && $line['applied'] !== '') {
                $explicit += (float) $line['applied'];
            }
        }
        $pool = max(0.0, $this->money($moneyReceived - $explicit));

        $result = [];
        $appliedTotal = 0.0;

        foreach ($lines as $line) {
            $label = 'Invoice FWSSi' . $line['invoice_id'];
            $outstanding = $this->money($line['outstanding'] ?? 0);
            $wht = $this->money($line['wht'] ?? 0);
            $vat = $this->money($line['vat7'] ?? 0);
            $ded = $this->money($line['deduction'] ?? 0);

            foreach (['WHT' => $wht, '7% VAT' => $vat, 'deduction' => $ded] as $name => $value) {
                if ($value < 0) {
                    $errors[] = "{$label}: {$name} cannot be negative.";
                }
            }

            $credits = $this->money($wht + $vat + $ded);
            $room = max(0.0, $this->money($outstanding - $credits));

            if ($credits > $outstanding + self::TOLERANCE) {
                $errors[] = "{$label}: WHT, VAT and deductions (" . number_format($credits, 2)
                    . ') are more than what is owed (' . number_format($outstanding, 2) . ').';
            }

            $typed = $line['applied'] ?? null;
            if ($typed === null || $typed === '') {
                // Auto-allocate from what has not been explicitly assigned.
                $applied = min($room, $pool);
                $pool = $this->money($pool - $applied);
            } else {
                $applied = $this->money($typed);
                if ($applied < 0) {
                    $errors[] = "{$label}: amount applied cannot be negative.";
                }
            }

            $settled = $this->money($applied + $credits);
            if ($settled > $outstanding + self::TOLERANCE) {
                $errors[] = "{$label}: GH₵" . number_format($settled, 2)
                    . ' is more than the GH₵' . number_format($outstanding, 2) . ' owed. Leave the extra as client credit instead.';
            }

            $remaining = max(0.0, $this->money($outstanding - $settled));
            $appliedTotal = $this->money($appliedTotal + $applied);

            $result[] = [
                'invoice_id' => (int) $line['invoice_id'],
                'outstanding' => $outstanding,
                'amount_applied' => $applied,
                'wht_amount' => $wht,
                'vat7_amount' => $vat,
                'deduction_amount' => $ded,
                'settled' => $settled,
                'remaining' => $remaining,
                'completes' => $remaining <= self::TOLERANCE,
            ];
        }

        if ($appliedTotal > $moneyReceived + self::TOLERANCE) {
            $errors[] = 'You have applied GH₵' . number_format($appliedTotal, 2)
                . ' but only GH₵' . number_format($moneyReceived, 2) . ' was received.';
        }

        return [
            'lines' => $result,
            'applied_total' => $appliedTotal,
            'unapplied' => max(0.0, $this->money($moneyReceived - $appliedTotal)),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    private function money($value): float
    {
        return round((float) $value, 2);
    }
}
