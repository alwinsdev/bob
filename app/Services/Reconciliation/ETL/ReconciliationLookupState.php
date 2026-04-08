<?php

namespace App\Services\Reconciliation\ETL;

class ReconciliationLookupState
{
    public array $imsByEmail = [];

    public array $imsByPhone = [];

    public array $imsByFirstLast = [];

    public array $imsByDobLast = [];

    public array $hsByEmail = [];

    public array $hsByPhoneDate = [];

    public array $payeeMap = [];

    public array $lockList = [];

    public array $lockListMisses = [];
}
