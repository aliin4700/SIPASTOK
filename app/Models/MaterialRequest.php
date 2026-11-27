<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'issued', 'received', 'cancelled'];

    public const RECEIPT_VALIDATION_RULES = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

    public const RECEIPT_REQUIRED_VALIDATION_RULES = ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

    public const RECEIPT_VALIDATION_MESSAGES = [
        'receipt.required' => 'Pilih atau ambil foto bukti struk / nota terlebih dahulu.',
        'receipt.file' => 'Bukti struk / nota harus berupa file yang valid.',
        'receipt.mimes' => 'Format bukti struk harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.',
        'receipt.max' => 'Ukuran file bukti struk maksimal 5MB.',
        'receipt.uploaded' => 'Gagal mengunggah file struk. Pastikan ukuran file tidak melebihi batas (maks 5MB).',
    ];

    protected $fillable = [
        'request_number',
        'division_daily_operation_id',
        'outlet_id',
        'division_id',
        'requested_by',
        'request_date',
        'status',
        'notes',
        'receipt_path',
        'receipt_original_name',
        'submitted_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $materialRequest): void {
            $materialRequest->deleteReceiptFile();
        });
    }

    public function dailyOperation()
    {
        return $this->belongsTo(DivisionDailyOperation::class, 'division_daily_operation_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function hasReceipt(): bool
    {
        return ! empty($this->receipt_path);
    }

    public function isReceiptPdf(): bool
    {
        if (! $this->hasReceipt()) {
            return false;
        }

        $extension = strtolower(pathinfo(
            (string) ($this->receipt_original_name ?: $this->receipt_path),
            PATHINFO_EXTENSION
        ));

        return $extension === 'pdf';
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->hasReceipt()) {
            return null;
        }

        return asset('storage/' . ltrim((string) $this->receipt_path, '/'));
    }

    /**
     * Store uploaded receipt file on the public disk with a collision-safe unique name.
     *
     * @return array{receipt_path: string, receipt_original_name: string}
     */
    public static function storeUploadedReceipt(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $uniqueName = sprintf(
            'struk-mr-%s-%s.%s',
            now()->format('YmdHis'),
            Str::lower(Str::random(8)),
            $extension
        );

        $storedPath = $file->storeAs('material-requests/receipts', $uniqueName, 'public');

        return [
            'receipt_path' => $storedPath,
            'receipt_original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
        ];
    }

    public static function deleteStoredReceipt(?string $path): void
    {
        if (! empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function deleteReceiptFile(): void
    {
        static::deleteStoredReceipt($this->receipt_path);
    }

    /**
     * Replace existing receipt file atomically: delete new file on DB failure, delete old file after save.
     */
    public function replaceReceipt(UploadedFile $file): void
    {
        $oldPath = $this->receipt_path;
        $uploaded = static::storeUploadedReceipt($file);

        try {
            $this->update($uploaded);
        } catch (\Throwable $exception) {
            static::deleteStoredReceipt($uploaded['receipt_path']);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $uploaded['receipt_path']) {
            static::deleteStoredReceipt($oldPath);
        }
    }

    /**
     * Clear receipt reference in database first, then remove file from public disk.
     */
    public function clearReceipt(): void
    {
        $oldPath = $this->receipt_path;

        $this->update([
            'receipt_path' => null,
            'receipt_original_name' => null,
        ]);

        static::deleteStoredReceipt($oldPath);
    }
}
