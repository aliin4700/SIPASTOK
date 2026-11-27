<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\UserDivisionAccess;
use App\Models\UserOutletAccess;
use App\Models\WasteRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimulationStaffRoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_staff_outlet_daily_operational_and_opname_simulation_on_mejasem(): void
    {
        $mejasem = Outlet::where('name', 'like', '%Mejasem%')->firstOrFail();

        // Cari akun staff_outlet yang memiliki akses ke outlet Mejasem (atau fallback staff aktif)
        $staff = User::where('role', 'staff_outlet')
            ->whereHas('outletAccesses', fn ($q) => $q->where('outlet_id', $mejasem->id))
            ->first()
            ?? User::where('role', 'staff_outlet')->firstOrFail();

        $divisionAccess = UserDivisionAccess::where('user_id', $staff->id)
            ->where('outlet_id', $mejasem->id)
            ->first();

        $divisionId = $divisionAccess?->division_id
            ?? Division::where('is_active', true)->orderBy('id')->value('id');

        // Pastikan staff memiliki akses aktif ke outlet Mejasem & divisi terkait dalam transaksi uji
        UserOutletAccess::firstOrCreate([
            'user_id' => $staff->id,
            'outlet_id' => $mejasem->id,
        ]);
        UserDivisionAccess::firstOrCreate([
            'user_id' => $staff->id,
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
        ]);

        // Ambil 3 item sampel dan pastikan aktif & terdaftar pada divisi staff di transaksi uji
        $strawberry = InventoryItem::whereRaw('UPPER(name) = ?', ['FROZEN STRAWBERRY'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['FROZEN STRAWBERRY'])
            ->firstOrFail();
        $gulaPasir = InventoryItem::whereRaw('UPPER(name) = ?', ['GULA PASIR'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['GULA PASIR'])
            ->firstOrFail();
        $strawberrySyrup = InventoryItem::whereRaw('UPPER(name) = ?', ['STRAWBERRY SYRUP'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['STRAWBERRY SYRUP'])
            ->firstOrFail();

        $strawberry->update(['division_id' => $divisionId, 'item_type' => 'raw_material', 'is_active' => true]);
        $gulaPasir->update(['division_id' => $divisionId, 'item_type' => 'raw_material', 'is_active' => true]);
        $strawberrySyrup->update(['division_id' => $divisionId, 'item_type' => 'wip', 'is_active' => true]);

        // Pastikan saldo awal item sampel di Mejasem mencukupi di dalam transaksi uji
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $strawberry->id],
            ['current_stock' => 7000, 'reserved_stock' => 0, 'available_stock' => 7000, 'unit' => 'GR']
        );
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $gulaPasir->id],
            ['current_stock' => 10000, 'reserved_stock' => 0, 'available_stock' => 10000, 'unit' => 'GR']
        );
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $strawberrySyrup->id],
            ['current_stock' => 500, 'reserved_stock' => 0, 'available_stock' => 500, 'unit' => 'GR']
        );

        // 1. Verifikasi Staff tidak bisa mengakses fitur Impor ESB
        $this->actingAs($staff)->get('/staff/esb-imports')->assertNotFound();
        $this->actingAs($staff)->get(route('manager.esb-imports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('backend.esb-imports.index'))->assertForbidden();

        // Buka Operasional Shift Hari Ini
        $this->actingAs($staff)->post(route('staff.daily-operations.open'), [
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
        ])->assertRedirect();

        // 2. Input Take Bahan (MaterialRequest): FROZEN STRAWBERRY 1.000 GR & GULA PASIR 500 GR
        $mrStore = $this->actingAs($staff)->post(route('staff.material-requests.store'), [
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'notes' => 'Simulasi Take Bahan Staff Mejasem',
            'items' => [
                ['inventory_item_id' => $strawberry->id, 'requested_qty' => 1000, 'unit' => 'GR'],
                ['inventory_item_id' => $gulaPasir->id, 'requested_qty' => 500, 'unit' => 'GR'],
            ],
        ]);
        $mrStore->assertRedirect();
        $mrStore->assertSessionHas('success');

        $mr = MaterialRequest::with('items')->latest('id')->firstOrFail();
        $this->assertSame('draft', $mr->status);
        $this->assertCount(2, $mr->items);

        $this->actingAs($staff)
            ->post(route('staff.material-requests.submit', $mr))
            ->assertRedirect()
            ->assertSessionHas('success');
        $mr->refresh();
        $this->assertSame('submitted', $mr->status);

        // 3. Input Produksi (ProductionRecord): STRAWBERRY SYRUP (1 batch = 1.500 GR)
        $prStore = $this->actingAs($staff)->post(route('staff.production.store'), [
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'production_type' => ProductionRecord::TYPES[0],
            'notes' => 'Simulasi Produksi 1 Batch Strawberry Syrup',
            'items' => [
                ['inventory_item_id' => $strawberrySyrup->id, 'qty' => 1500, 'unit' => 'GR'],
            ],
        ]);
        $prStore->assertRedirect();
        $prStore->assertSessionHas('success');

        $pr = ProductionRecord::with('items')->latest('id')->firstOrFail();
        $this->assertSame('draft', $pr->status);
        $this->assertCount(1, $pr->items);

        $this->actingAs($staff)
            ->post(route('staff.production.submit', $pr))
            ->assertRedirect()
            ->assertSessionHas('success');
        $pr->refresh();
        $this->assertSame('submitted', $pr->status);

        // 4. Input Waste (WasteRecord): FROZEN STRAWBERRY 50 GR
        $wrStore = $this->actingAs($staff)->post(route('staff.waste.store'), [
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'notes' => 'Simulasi Waste Staff Mejasem',
            'items' => [
                [
                    'inventory_item_id' => $strawberry->id,
                    'qty' => 50,
                    'unit' => 'GR',
                    'reason' => 'damaged',
                ],
            ],
        ]);
        $wrStore->assertRedirect();
        $wrStore->assertSessionHas('success');

        $wr = WasteRecord::with('items')->latest('id')->firstOrFail();
        $this->assertSame('draft', $wr->status);
        $this->assertCount(1, $wr->items);

        $this->actingAs($staff)
            ->post(route('staff.waste.submit', $wr))
            ->assertRedirect()
            ->assertSessionHas('success');
        $wr->refresh();
        $this->assertSame('submitted', $wr->status);

        // 5. Input Stock Opname (Blind Count) & Submit
        $physicalStrawberry = 6850.0;
        $physicalGula = 9950.0;
        $physicalSyrup = 1950.0;

        $soStore = $this->actingAs($staff)->post(route('staff.stock-opnames.store'), [
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'opname_type' => 'daily_closing',
            'is_blind_count' => 1,
            'notes' => 'Simulasi Closing Stock Opname Blind Count Staff Mejasem',
            'items' => [
                ['inventory_item_id' => $strawberry->id, 'physical_stock' => $physicalStrawberry],
                ['inventory_item_id' => $gulaPasir->id, 'physical_stock' => $physicalGula],
                ['inventory_item_id' => $strawberrySyrup->id, 'physical_stock' => $physicalSyrup],
            ],
        ]);
        $soStore->assertRedirect();
        $soStore->assertSessionHas('success');

        $so = StockOpname::with('items.item')->latest('id')->firstOrFail();
        $this->assertSame('draft', $so->status);
        $this->assertTrue((bool) $so->is_blind_count);
        $this->assertCount(3, $so->items);

        $this->actingAs($staff)
            ->post(route('staff.stock-opnames.submit', $so))
            ->assertRedirect()
            ->assertSessionHas('success');
        $so->refresh();
        $this->assertSame('submitted', $so->status);

        // 6. Cetak ringkasan status dokumen & angka fisik opname ke console
        $log = "\n"
            . "====================================================================================\n"
            . "  SIMULASI ROLE STAFF OUTLET — OPERASIONAL & BLIND COUNT (OUTLET: {$mejasem->name})\n"
            . "====================================================================================\n"
            . sprintf("  Staff         : %s (%s)\n", $staff->name, $staff->email)
            . "  Akses ESB     : /staff/esb-imports [404], /manager & /backend [403 Forbidden]\n"
            . "------------------------------------------------------------------------------------\n"
            . sprintf("  1. Take Bahan : %-24s | Status: %-10s | 2 item (1.000 & 500 GR)\n", $mr->request_number, strtoupper($mr->status))
            . sprintf("  2. Produksi   : %-24s | Status: %-10s | 1 batch (1.500 GR WIP)\n", $pr->production_number, strtoupper($pr->status))
            . sprintf("  3. Waste      : %-24s | Status: %-10s | 1 item (50 GR damaged)\n", $wr->waste_number, strtoupper($wr->status))
            . sprintf("  4. Opname     : %-24s | Status: %-10s | Blind Count: YA (3 item)\n", $so->opname_number, strtoupper($so->status))
            . "------------------------------------------------------------------------------------\n"
            . sprintf("  %-22s | %12s | %12s | %12s\n", 'Item Opname', 'Stok Sistem', 'Stok Fisik', 'Selisih')
            . "------------------------------------------------------------------------------------\n";

        foreach ($so->items as $opItem) {
            $log .= sprintf(
                "  %-22s | %9s GR | %9s GR | %9s GR\n",
                $opItem->item->name ?? '-',
                number_format((float) $opItem->system_stock, 2, ',', '.'),
                number_format((float) $opItem->physical_stock, 2, ',', '.'),
                number_format((float) $opItem->difference_qty, 2, ',', '.')
            );
        }

        $log .= "====================================================================================\n";
        fwrite(STDOUT, $log);
    }
}
