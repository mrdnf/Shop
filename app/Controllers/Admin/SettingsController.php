<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Settings;

final class SettingsController extends AdminController
{
    public function edit(): string
    {
        return $this->view('admin/settings/edit', ['title' => __('Store settings'), 's' => Settings::all()]);
    }

    public function update(): never
    {
        $data = $this->validate([
            'currency' => 'required|max:10',
            'currency_symbol' => 'required|max:10',
            'symbol_position' => 'required|in:before,after',
            'decimals' => 'required|integer|min:0|max:3',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'order_prefix' => 'required|max:10',
            'tracking_prefix' => 'required|max:6',
            'email' => 'nullable|email',
            'phone' => 'nullable|max:40',
            'address' => 'nullable|max:500',
            'tax_number' => 'nullable|max:60',
            'bank_details' => 'nullable|max:2000',
        ]);
        Settings::setMany([
            'store.currency' => strtoupper((string) $data['currency']),
            'store.currency_symbol' => $data['currency_symbol'],
            'store.symbol_position' => $data['symbol_position'],
            'store.decimals' => $data['decimals'],
            'store.tax_rate' => $data['tax_rate'],
            'store.order_prefix' => $data['order_prefix'],
            'store.allow_guest_checkout' => Request::input('allow_guest_checkout') === '1' ? '1' : '0',
            'delivery.tracking_prefix' => $data['tracking_prefix'],
            'company.email' => (string) $data['email'],
            'company.phone' => (string) $data['phone'],
            'company.address' => (string) $data['address'],
            'company.tax_number' => (string) $data['tax_number'],
            'payment.cod' => Request::input('payment_cod') === '1' ? '1' : '0',
            'payment.bank' => Request::input('payment_bank') === '1' ? '1' : '0',
            'payment.bank_details' => (string) $data['bank_details'],
        ]);
        flash('success', __('Settings saved.'));
        redirect(url('/admin/settings'));
    }
}
