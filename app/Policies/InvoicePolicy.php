<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return $user->can('invoices.view'); }
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view') && $user->tenant_id === $invoice->tenant_id;
    }
    public function create(User $user): bool { return $user->can('invoices.create'); }
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.edit') && $user->tenant_id === $invoice->tenant_id;
    }
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.edit') && $invoice->isVoidable() && $user->tenant_id === $invoice->tenant_id;
    }
}
