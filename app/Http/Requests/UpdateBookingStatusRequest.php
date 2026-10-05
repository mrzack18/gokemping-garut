<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi perubahan status booking (ROADMAP 4.4).
 *
 * Form request ini hanya memeriksa bentuk nilai yang dikirim. Aturan boleh atau
 * tidaknya status berubah tetap dipegang `BookingStatus::canTransitionTo()`,
 * karena itu aturan yang sama dipakai controller, service, dan test.
 */
class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_column(BookingStatus::cases(), 'value')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status baru wajib dipilih.',
            'status.in' => 'Status booking tidak dikenal.',
        ];
    }

    /**
     * Tolak perubahan yang tidak sah di level request, bukan setelah database
     * disentuh.
     *
     * Booking dibaca dari `$this->route('booking')`, jadi nilainya sudah ter-scope
     * `BusinessScope`: admin yang menebak kode booking unit lain tidak pernah
     * sampai ke sini, dan request-nya sudah berhenti sebagai 404.
     */
    public function withValidator(Validator $validator): void
    {
        $booking = $this->booking();
        $target = $this->status();

        $validator->after(function (Validator $validator) use ($booking, $target): void {
            if ($booking === null || $target === null) {
                return;
            }

            if (! $booking->booking_status->canTransitionTo($target)) {
                $validator->errors()->add(
                    'status',
                    'Booking dengan status "'.$booking->booking_status->label()
                        .'" tidak bisa diubah menjadi "'.$target->label().'".',
                );
            }
        });
    }

    /**
     * Booking dari route, kalau route model binding sudah memuatnya.
     *
     * Nilai route selalu berupa model `Booking` kalau binding berhasil, dan
     * proses berhenti sebagai 404 kalau tidak. Nilai itu dibaca di sini supaya
     * aturan validasi tidak bergantung pada tebakan bentuk nilai route.
     */
    private function booking(): ?Booking
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking ? $booking : null;
    }

    /**
     * Status tujuan, atau null kalau nilai yang dikirim tidak dikenal.
     */
    public function status(): ?BookingStatus
    {
        $value = $this->input('status');

        if (! is_string($value)) {
            return null;
        }

        return BookingStatus::tryFrom($value);
    }
}
