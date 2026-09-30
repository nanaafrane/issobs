<?php

namespace App\Models;

use App\Support\PayPriority;
use Illuminate\Database\Eloquent\Model;

/** One payment-priority rule. Editing or deleting a rule re-evaluates employees and unpaid salaries. */
class PayPriorityRule extends Model
{
    protected $fillable = ['client_id', 'level', 'gender', 'field_id', 'locations', 'reason', 'active'];

    protected $casts = [
        'locations' => 'array',
        'active' => 'boolean',
        'level' => 'integer',
    ];

    protected static function booted(): void
    {
        // Rules may only target default Category A clients (category::DEFAULT_A_CLIENT_IDS).
        // Also catches mistyped client ids, which are otherwise silent.
        static::saving(function (self $rule) {
            if (! in_array((int) $rule->client_id, PayPriority::scopeClientIds(), true)) {
                throw new \InvalidArgumentException(
                    "Client {$rule->client_id} is not a default Category A client. "
                    . 'Add it to category::DEFAULT_A_CLIENT_IDS before giving it a pay rule.'
                );
            }
        });

        $recompute = function () {
            PayPriority::recomputeEmployees();
            PayPriority::recomputeSalaries();
        };
        static::saved($recompute);
        static::deleted($recompute);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
