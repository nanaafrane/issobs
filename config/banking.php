<?php

return [
    /*
     * MoMo withdrawal charge, as a percentage of the amount withdrawn, used only
     * to PRE-FILL the charge box on Bank Deposit (e.g. 1 for 1%). The person
     * depositing always enters / confirms the real charge from the MoMo SMS.
     * Leave empty to have no suggestion.
     */
    'momo_charge_rate' => env('MOMO_WITHDRAWAL_CHARGE_RATE'),
];
