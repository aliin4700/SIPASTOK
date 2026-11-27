<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserDivisionAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialRequestReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private function createFixtures(): array
    {
        $suffix = Str::lower(Str::random(6));

        $outlet = Outlet::create([
            'name' => 'Outlet Test ' . $suffix,
            'code' => 'OT' . strtoupper($suffix),
            'is_active' => true,
        ]);

        $division = Division::create([
            'name' => 'Divisi Test ' . $suffix,
            'code' => 'DT' . strtoupper($suffix),
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'division_id' => $division->id,
            'name' => 'Tepung Test ' . $suffix,
            'code' => 'RM-' . strtoupper($suffix),
            'item_type' => 'raw_material',
            'base_unit' => 'gram',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff Test ' . $suffix,
            'email' => "staff-{$suffix}@sipastok.test",
            'password' => bcrypt('password'),
            'role' => 'staff_outlet',
            'status' => 'active',
        ]);

        $manager = User::create([
            'name' => 'Manager Test ' . $suffix,
            'email' => "manager-{$suffix}@sipastok.test",
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $admin = User::create([
            'name' => 'Admin Test ' . $suffix,
            'email' => "admin-{$suffix}@sipastok.test",
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        UserDivisionAccess::create([
            'user_id' => $staff->id,
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'can_input_stock' => true,
            'can_input_waste' => true,
            'can_request_order' => true,
        ]);

        DivisionDailyOperation::create([
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'operation_date' => today(),
            'opened_by' => $staff->id,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        return compact('outlet', 'division', 'item', 'staff', 'manager', 'admin');
    }

    private function fakePng(string $name = 'struk-kamera.png'): UploadedFile
    {
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $pngBytes);
    }

    private function fakePdf(string $name = 'nota-gudang.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n"
        );
    }

    public function test_staff_can_create_material_request_without_receipt(): void
    {
        Storage::fake('public');
        $fixtures = $this->createFixtures();

        $response = $this->actingAs($fixtures['staff'])->post(route('staff.material-requests.store'), [
            'outlet_id' => $fixtures['outlet']->id,
            'division_id' => $fixtures['division']->id,
            'notes' => 'Tanpa lampiran struk',
            'items' => [
                [
                    'inventory_item_id' => $fixtures['item']->id,
                    'requested_qty' => 500,
                    'unit' => 'gram',
                ],
            ],
        ]);

        $materialRequest = MaterialRequest::where('outlet_id', $fixtures['outlet']->id)->latest('id')->firstOrFail();

        $response->assertRedirect(route('staff.material-requests.show', $materialRequest));
        $this->assertNull($materialRequest->receipt_path);
        $this->assertFalse($materialRequest->hasReceipt());
    }

    public function test_staff_can_upload_replace_and_backend_can_delete_receipt_without_orphans(): void
    {
        Storage::fake('public');
        $fixtures = $this->createFixtures();

        // 1. Create with PNG image
        $response = $this->actingAs($fixtures['staff'])->post(route('staff.material-requests.store'), [
            'outlet_id' => $fixtures['outlet']->id,
            'division_id' => $fixtures['division']->id,
            'notes' => 'Dengan foto kamera',
            'receipt' => $this->fakePng('foto-nota-toko.png'),
            'items' => [
                [
                    'inventory_item_id' => $fixtures['item']->id,
                    'requested_qty' => 250,
                    'unit' => 'gram',
                ],
            ],
        ]);

        $materialRequest = MaterialRequest::where('outlet_id', $fixtures['outlet']->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('staff.material-requests.show', $materialRequest));

        $firstPath = $materialRequest->receipt_path;
        $this->assertNotNull($firstPath);
        $this->assertStringStartsWith('material-requests/receipts/', $firstPath);
        $this->assertSame('foto-nota-toko.png', $materialRequest->receipt_original_name);
        $this->assertTrue($materialRequest->hasReceipt());
        $this->assertFalse($materialRequest->isReceiptPdf());
        Storage::disk('public')->assertExists($firstPath);

        // Staff download returns 200
        $this->actingAs($fixtures['staff'])
            ->get(route('staff.material-requests.receipt.download', $materialRequest))
            ->assertOk();

        // 2. Replace via Staff edit/update with PDF -> old PNG removed
        $this->actingAs($fixtures['staff'])
            ->put(route('staff.material-requests.update', $materialRequest), [
                'notes' => 'Diganti PDF',
                'receipt' => $this->fakePdf('invoice-supplier.pdf'),
                'items' => [
                    [
                        'inventory_item_id' => $fixtures['item']->id,
                        'requested_qty' => 300,
                        'unit' => 'gram',
                    ],
                ],
            ])
            ->assertRedirect(route('staff.material-requests.show', $materialRequest));

        $materialRequest->refresh();
        $secondPath = $materialRequest->receipt_path;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertTrue($materialRequest->isReceiptPdf());
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        // 3. Manager & Backend can view show page cleanly
        $this->actingAs($fixtures['manager'])
            ->get(route('manager.material-requests.show', $materialRequest))
            ->assertOk()
            ->assertSee('invoice-supplier.pdf');

        $this->actingAs($fixtures['admin'])
            ->get(route('backend.material-requests.show', $materialRequest))
            ->assertOk()
            ->assertSee('invoice-supplier.pdf');

        // 4. Delete draft via Backend -> PDF removed too (no orphan files)
        $this->actingAs($fixtures['admin'])
            ->delete(route('backend.material-requests.destroy', $materialRequest))
            ->assertRedirect(route('backend.material-requests.index'));

        $this->assertDatabaseMissing('material_requests', ['id' => $materialRequest->id]);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_manager_can_update_and_delete_receipt_and_staff_can_clear_via_edit(): void
    {
        Storage::fake('public');
        $fixtures = $this->createFixtures();

        $this->actingAs($fixtures['staff'])->post(route('staff.material-requests.store'), [
            'outlet_id' => $fixtures['outlet']->id,
            'division_id' => $fixtures['division']->id,
            'receipt' => $this->fakePng('awal.png'),
            'items' => [
                [
                    'inventory_item_id' => $fixtures['item']->id,
                    'requested_qty' => 150,
                ],
            ],
        ]);

        $materialRequest = MaterialRequest::where('outlet_id', $fixtures['outlet']->id)->latest('id')->firstOrFail();
        $initialPath = $materialRequest->receipt_path;
        Storage::disk('public')->assertExists($initialPath);

        // Staff removes receipt during draft edit via remove_receipt=1
        $this->actingAs($fixtures['staff'])
            ->put(route('staff.material-requests.update', $materialRequest), [
                'remove_receipt' => '1',
                'items' => [
                    [
                        'inventory_item_id' => $fixtures['item']->id,
                        'requested_qty' => 150,
                    ],
                ],
            ])
            ->assertRedirect(route('staff.material-requests.show', $materialRequest));

        $materialRequest->refresh();
        $this->assertNull($materialRequest->receipt_path);
        Storage::disk('public')->assertMissing($initialPath);

        // Manager attaches a receipt directly on detail page
        $this->actingAs($fixtures['manager'])
            ->post(route('manager.material-requests.receipt.update', $materialRequest), [
                'receipt' => $this->fakePng('manager-audit.png'),
            ])
            ->assertRedirect();

        $materialRequest->refresh();
        $managerPath = $materialRequest->receipt_path;
        $this->assertNotNull($managerPath);
        Storage::disk('public')->assertExists($managerPath);

        // Manager deletes attachment cleanly
        $this->actingAs($fixtures['manager'])
            ->delete(route('manager.material-requests.receipt.destroy', $materialRequest))
            ->assertRedirect();

        $materialRequest->refresh();
        $this->assertNull($materialRequest->receipt_path);
        Storage::disk('public')->assertMissing($managerPath);
    }

    public function test_invalid_receipt_file_is_rejected(): void
    {
        Storage::fake('public');
        $fixtures = $this->createFixtures();

        $invalidFile = UploadedFile::fake()->createWithContent('catatan.txt', 'hello world');

        $response = $this->actingAs($fixtures['staff'])->post(route('staff.material-requests.store'), [
            'outlet_id' => $fixtures['outlet']->id,
            'division_id' => $fixtures['division']->id,
            'receipt' => $invalidFile,
            'items' => [
                [
                    'inventory_item_id' => $fixtures['item']->id,
                    'requested_qty' => 100,
                    'unit' => 'gram',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('receipt');
    }
}
