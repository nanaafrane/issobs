<?php

namespace App\Services\Banking;

use App\Models\Bank;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Plans one move of money between our own accounts — any number of sources, any number of
 * destinations — without touching the database. AccountBook::move() posts the plan; the
 * "Move money" screen shows it as a live preview, so what is previewed is what is saved.
 *
 * How money can LEAVE an account:            How it can ARRIVE:
 *   bank:  transfer | cheque | withdrawal      bank:  transfer (received) | cheque (deposited) | cash (deposited)
 *   cash:  cash                                cash:  cash (received)
 *
 * Which source can feed which destination:
 *   transfer out          -> transfer received                (amounts can be split freely)
 *   cheque                -> deposited whole into ONE bank     (dest. line names the cheque)
 *                         -> or cashed, and the cash deposited / kept anywhere
 *   withdrawal, cash      -> cash deposited / cash received
 * So: transfers out = transfers received, and cashed cheques + withdrawals + cash = cash in.
 *
 * Each account gets the lines its own statement shows: a cheque or withdrawal is ONE debit
 * however it is shared out; each deposit slip (cash / cheque deposited) is ONE credit; a bank
 * transfer is one debit and one credit per pair. No account can be on both sides of a move.
 */
class MovePlan
{
    public const LEAVE = [
        'transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'withdrawal' => 'Cash withdrawal',
        'cash' => 'Cash',
    ];

    public const ARRIVE = [
        'transfer' => 'Transfer received',
        'cheque' => 'Cheque deposited',
        'cash' => 'Cash',
    ];

    /** @var array<int, array{bank: Bank, how: string, amount: float, reference: ?string, date: Carbon}> */
    public array $sources = [];

    /** @var array<int, array{bank: Bank, how: string, amount: float, reference: ?string, date: Carbon, cheque: ?int}> */
    public array $destinations = [];

    /** @var array<int, array{out: int, in: int, amount: float, method: string, in_method: string}> who funded what */
    public array $pairs = [];

    /** @var array<int, array{bank: Bank, amount: float, channel: string, date: Carbon, narration: string, pair: int}> */
    public array $lines = [];

    public float $total = 0.0;

    public static function leaveOptions(Bank $b): array
    {
        return $b->isCash() ? ['cash'] : ['transfer', 'cheque', 'withdrawal'];
    }

    public static function arriveOptions(Bank $b): array
    {
        return $b->isCash() ? ['cash'] : ['transfer', 'cheque', 'cash'];
    }

    /**
     * @param  array{date?:?string, notes?:?string, sources:array, destinations:array}  $input
     * @param  Collection<int, Bank>  $banks  keyed by id (locked by the caller when saving)
     */
    public static function make(array $input, Collection $banks): self
    {
        $p = new self();
        $date = self::day($input['date'] ?? null, 'date') ?? now()->startOfDay();
        $notes = trim((string) ($input['notes'] ?? ''));
        $suffix = $notes !== '' ? ' — ' . mb_substr($notes, 0, 120) : '';

        $p->readSources((array) ($input['sources'] ?? []), $banks, $date);
        $p->readDestinations((array) ($input['destinations'] ?? []), $banks, $date);
        $p->checkSides();
        $p->match();
        $p->post($suffix);

        return $p;
    }

    // ------------------------------------------------------------------ input

    private function readSources(array $rows, Collection $banks, Carbon $date): void
    {
        foreach (array_values($rows) as $i => $r) {
            if (empty($r['bank_id']) && (float) ($r['amount'] ?? 0) <= 0) {
                continue;   // an empty line left on the form
            }
            $f = "sources.$i";
            $bank = $banks->get((int) ($r['bank_id'] ?? 0));
            if (! $bank) {
                self::fail("$f.bank_id", 'Choose the account the money comes out of (source ' . ($i + 1) . ').');
            }
            self::operating($bank, "$f.bank_id");
            $how = (string) ($r['how'] ?? '');
            if (! in_array($how, self::leaveOptions($bank), true)) {
                self::fail("$f.how", $bank->isCash() ? $bank->name . ' is cash: the money leaves it as cash.' : 'Say how the money leaves ' . $bank->name . ': transfer, cheque or cash withdrawal.');
            }
            $amount = round((float) ($r['amount'] ?? 0), 2);
            if ($amount <= 0) {
                self::fail("$f.amount", 'Enter the amount out of ' . $bank->name . '.');
            }
            $ref = self::clean($r['reference'] ?? null);
            if ($how === 'cheque' && ! $ref) {
                self::fail("$f.reference", 'Enter the cheque number (' . $bank->name . ').');
            }
            $this->sources[$i] = ['bank' => $bank, 'how' => $how, 'amount' => $amount, 'reference' => $ref, 'date' => self::day($r['date'] ?? null, "$f.date") ?? $date];
        }
        if (! $this->sources) {
            self::fail('sources', 'Add the account(s) the money comes out of.');
        }
    }

    private function readDestinations(array $rows, Collection $banks, Carbon $date): void
    {
        foreach (array_values($rows) as $j => $r) {
            if (empty($r['bank_id']) && (float) ($r['amount'] ?? 0) <= 0) {
                continue;
            }
            $f = "destinations.$j";
            $bank = $banks->get((int) ($r['bank_id'] ?? 0));
            if (! $bank) {
                self::fail("$f.bank_id", 'Choose the account the money goes into (destination ' . ($j + 1) . ').');
            }
            self::operating($bank, "$f.bank_id");
            $how = (string) ($r['how'] ?? ($bank->isCash() ? 'cash' : ''));
            if (! in_array($how, self::arriveOptions($bank), true)) {
                self::fail("$f.how", $bank->isCash() ? $bank->name . ' is cash: it receives cash (cash a cheque first).' : 'Say how the money arrives in ' . $bank->name . ': transfer, cheque deposited or cash deposited.');
            }
            $cheque = null;
            $amount = round((float) ($r['amount'] ?? 0), 2);
            if ($how === 'cheque') {
                $cheque = isset($r['cheque']) && $r['cheque'] !== '' ? (int) $r['cheque'] : null;
                if ($cheque === null || ($this->sources[$cheque]['how'] ?? null) !== 'cheque') {
                    $cheques = collect($this->sources)->where('how', 'cheque');
                    if ($cheques->count() === 1) {
                        $cheque = (int) $cheques->keys()->first();   // the only cheque on the move
                    } else {
                        self::fail("$f.cheque", $cheques->isEmpty() ? 'No cheque is going out on this move, so none can be deposited into ' . $bank->name . '.' : 'Choose which cheque is deposited into ' . $bank->name . '.');
                    }
                }
                $amount = $this->sources[$cheque]['amount'];   // a cheque is deposited whole
            }
            if ($amount <= 0) {
                self::fail("$f.amount", 'Enter the amount into ' . $bank->name . '.');
            }
            $this->destinations[$j] = ['bank' => $bank, 'how' => $how, 'amount' => $amount, 'reference' => self::clean($r['reference'] ?? null),
                'date' => self::day($r['date'] ?? null, "$f.date") ?? $date, 'cheque' => $cheque];
        }
        if (! $this->destinations) {
            self::fail('destinations', 'Add the account(s) the money goes into.');
        }
    }

    private function checkSides(): void
    {
        $out = collect($this->sources)->map(fn ($s) => $s['bank']->id);
        foreach ($this->destinations as $j => $d) {
            if ($out->contains($d['bank']->id)) {
                self::fail("destinations.$j.bank_id", $d['bank']->name . ' is also a source. Money cannot go out of and into the same account in one move.');
            }
        }
        $deposited = collect($this->destinations)->where('how', 'cheque')->pluck('cheque');
        foreach ($deposited->duplicates() as $i) {
            self::fail('destinations', 'Cheque ' . $this->sources[$i]['reference'] . ' is deposited twice. A cheque goes whole into one account; to share it, cash it and deposit the cash.');
        }

        $out = round(collect($this->sources)->sum('amount'), 2);
        $in = round(collect($this->destinations)->sum('amount'), 2);
        if (abs($out - $in) > 0.004) {
            self::fail('balance', sprintf('GH₵ %s comes out but GH₵ %s goes in (difference GH₵ %s). Both sides must match.',
                number_format($out, 2), number_format($in, 2), number_format(abs($out - $in), 2)));
        }
        $this->total = $out;

        $tOut = round(collect($this->sources)->where('how', 'transfer')->sum('amount'), 2);
        $tIn = round(collect($this->destinations)->where('how', 'transfer')->sum('amount'), 2);
        if (abs($tOut - $tIn) > 0.004) {
            self::fail('balance', sprintf('Bank transfers out total GH₵ %s but transfers received total GH₵ %s. A transfer arrives as a transfer; cash and cheques arrive as cash or a cheque deposit.',
                number_format($tOut, 2), number_format($tIn, 2)));
        }
    }

    // ------------------------------------------------------------------ matching

    private function match(): void
    {
        // Transfers: in the order entered, split as needed.
        $this->greedy(array_keys(array_filter($this->sources, fn ($s) => $s['how'] === 'transfer')),
            array_keys(array_filter($this->destinations, fn ($d) => $d['how'] === 'transfer')));

        // Cheques deposited whole.
        foreach ($this->destinations as $j => $d) {
            if ($d['how'] === 'cheque') {
                $this->pair($d['cheque'], $j, $d['amount']);
            }
        }

        // Everything else becomes cash: cheques not deposited (cashed), withdrawals, cash.
        $deposited = collect($this->destinations)->where('how', 'cheque')->pluck('cheque')->all();
        $cashOut = array_keys(array_filter($this->sources, fn ($s, $i) => $s['how'] !== 'transfer' && ! in_array($i, $deposited, true), ARRAY_FILTER_USE_BOTH));
        $cashIn = array_keys(array_filter($this->destinations, fn ($d) => $d['how'] === 'cash'));
        $this->greedy($cashOut, $cashIn);

        foreach ($this->pairs as $p) {
            if ($this->destinations[$p['in']]['date']->lt($this->sources[$p['out']]['date'])) {
                self::fail("destinations.{$p['in']}.date", 'Money cannot arrive in ' . $this->destinations[$p['in']]['bank']->name . ' before it leaves ' . $this->sources[$p['out']]['bank']->name . '.');
            }
        }
    }

    private function greedy(array $outs, array $ins): void
    {
        $left = array_combine($outs, array_map(fn ($i) => $this->sources[$i]['amount'], $outs)) ?: [];
        foreach ($ins as $j) {
            $need = $this->destinations[$j]['amount'];
            foreach ($left as $i => $have) {
                if ($need <= 0.004) {
                    break;
                }
                if ($have <= 0.004) {
                    continue;
                }
                $take = round(min($have, $need), 2);
                $this->pair($i, $j, $take);
                $left[$i] = round($have - $take, 2);
                $need = round($need - $take, 2);
            }
            if ($need > 0.004) {
                self::fail("destinations.$j.amount", sprintf('GH₵ %s of the money into %s has no source that can pay it in that form. Cash in comes from cheques cashed, withdrawals or cash; a transfer received comes from a transfer out.',
                    number_format($need, 2), $this->destinations[$j]['bank']->name));
            }
        }
        foreach ($left as $i => $have) {
            if ($have > 0.004) {
                self::fail("sources.$i.amount", sprintf('GH₵ %s out of %s (%s) does not reach any account in a form it can arrive in.',
                    number_format($have, 2), $this->sources[$i]['bank']->name, strtolower(self::LEAVE[$this->sources[$i]['how']])));
            }
        }
    }

    private function pair(int $i, int $j, float $amount): void
    {
        $s = $this->sources[$i];
        $d = $this->destinations[$j];
        $method = $s['how'] === 'cash' ? ($d['bank']->isCash() ? 'handover' : 'deposit') : $s['how'];
        $this->pairs[] = ['out' => $i, 'in' => $j, 'amount' => round($amount, 2), 'method' => $method, 'in_method' => $d['how']];
    }

    // ------------------------------------------------------------------ statement lines

    private function post(string $suffix): void
    {
        $m = fn ($v) => number_format((float) $v, 2);
        foreach ($this->sources as $i => $s) {
            $mine = array_filter($this->pairs, fn ($p) => $p['out'] === $i);
            $ref = $s['reference'] ? ' ' . $s['reference'] : '';
            if ($s['how'] === 'transfer') {
                foreach ($mine as $k => $p) {
                    $this->line($s['bank'], -$p['amount'], 'transfer_out', $s['date'], 'Transfer' . $ref . ' to ' . $this->destinations[$p['in']]['bank']->name . $suffix, $k);
                }
                continue;
            }
            $to = collect($mine)->map(fn ($p) => $this->destinations[$p['in']]['bank']->name . (count($mine) > 1 ? ' ' . $m($p['amount']) : ''))->implode(', ');
            $label = ['cheque' => 'Cheque', 'withdrawal' => 'Cash withdrawal', 'cash' => 'Cash'][$s['how']];
            $what = match (true) {
                $s['how'] === 'cheque' && collect($mine)->contains(fn ($p) => $p['in_method'] === 'cheque') => 'Cheque' . $ref . ' deposited into ' . $to,
                count($mine) === 1 => $label . $ref . ' to ' . $to,                 // e.g. "Cheque 000457 to Main Cash"
                $s['how'] === 'cheque' => 'Cheque' . $ref . ' cashed — ' . $to,     // shared out as cash
                default => $label . $ref . ' — ' . $to,
            };
            $this->line($s['bank'], -$s['amount'], 'transfer_out', $s['date'], $what . $suffix, array_key_first($mine));
        }

        foreach ($this->destinations as $j => $d) {
            $mine = array_filter($this->pairs, fn ($p) => $p['in'] === $j);
            $slip = $d['reference'] ? ' (ref ' . $d['reference'] . ')' : '';
            if ($d['how'] === 'transfer') {
                foreach ($mine as $k => $p) {
                    $s = $this->sources[$p['out']];
                    $this->line($d['bank'], $p['amount'], 'transfer_in', $d['date'], 'Transfer' . ($s['reference'] ? ' ' . $s['reference'] : '') . ' from ' . $s['bank']->name . $slip . $suffix, $k);
                }
                continue;
            }
            $from = collect($mine)->map(function ($p) use ($m, $mine) {
                $s = $this->sources[$p['out']];
                $label = match ($s['how']) {
                    'cheque' => 'cheque ' . $s['reference'] . ' (' . $s['bank']->name . ')',
                    'withdrawal' => 'withdrawal from ' . $s['bank']->name,
                    default => 'cash from ' . $s['bank']->name,
                };

                return $label . (count($mine) > 1 ? ' ' . $m($p['amount']) : '');
            })->implode(', ');
            $what = match (true) {
                $d['how'] === 'cheque' => 'Cheque deposited' . $slip . ' — ' . $from,
                $d['bank']->isCash() => 'Cash received — ' . $from,
                default => 'Cash deposited' . $slip . ' — ' . $from,
            };
            $this->line($d['bank'], $d['amount'], 'transfer_in', $d['date'], $what . $suffix, array_key_first($mine));
        }
    }

    private function line(Bank $bank, float $amount, string $channel, Carbon $date, string $narration, int $pair): void
    {
        $this->lines[] = ['bank' => $bank, 'amount' => round($amount, 2), 'channel' => $channel, 'date' => $date, 'narration' => mb_substr($narration, 0, 255), 'pair' => $pair];
    }

    // ------------------------------------------------------------------ helpers

    private static function operating(Bank $bank, string $field): void
    {
        if ($bank->is_active === false) {
            self::fail($field, $bank->name . ' is closed.');
        }
    }

    private static function day(mixed $v, string $field): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            $d = Carbon::parse($v)->startOfDay();
        } catch (\Throwable) {
            self::fail($field, 'Enter a valid date.');
        }
        if ($d->gt(now()->endOfDay())) {
            self::fail($field, 'The date cannot be in the future.');
        }

        return $d;
    }

    private static function clean(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : mb_substr($v, 0, 60);
    }

    private static function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
