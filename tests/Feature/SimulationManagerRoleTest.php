<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\EsbSalesImport;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\WasteRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SimulationManagerRoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manager_full_closing_approval_esb_import_and_opname_simulation_on_mejasem(): void
    {
        // 1. Login sebagai Manager & siapkan outlet Mejasem + 3 item sampel + dokumen pending dari Staff
        $manager = User::where('role', 'manager')->firstOrFail();
        $staff = User::where('role', 'staff_outlet')->firstOrFail();
        $mejasem = Outlet::where('name', 'like', '%Mejasem%')->firstOrFail();
        $divisionId = Division::where('is_active', true)->orderBy('id')->value('id');

        $strawberry = InventoryItem::whereRaw('UPPER(name) = ?', ['FROZEN STRAWBERRY'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['FROZEN STRAWBERRY'])
            ->firstOrFail();
        $gulaPasir = InventoryItem::whereRaw('UPPER(name) = ?', ['GULA PASIR'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['GULA PASIR'])
            ->firstOrFail();
        $strawberrySyrup = InventoryItem::whereRaw('UPPER(name) = ?', ['STRAWBERRY SYRUP'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['STRAWBERRY SYRUP'])
            ->firstOrFail();

        // Pastikan resep BOM STRAWBERRY SYRUP (1 batch = 1500 GR -> 1000 GR Strawberry + 500 GR Gula Pasir) aktif & terhubung
        $recipe = Recipe::where('output_inventory_item_id', $strawberrySyrup->id)
            ->where('is_active', true)
            ->first();

        if (! $recipe) {
            $recipe = Recipe::create([
                'recipe_code' => 'TEST-BOM-STRW-' . uniqid(),
                'name' => 'Strawberry Syrup',
                'output_inventory_item_id' => $strawberrySyrup->id,
                'output_qty' => 1500,
                'output_unit' => 'GR',
                'division_id' => $divisionId,
                'is_active' => true,
            ]);
            $recipe->items()->create(['inventory_item_id' => $strawberry->id, 'qty' => 1000, 'unit' => 'GR', 'waste_percentage' => 0]);
            $recipe->items()->create(['inventory_item_id' => $gulaPasir->id, 'qty' => 500, 'unit' => 'GR', 'waste_percentage' => 0]);
        }

        // Pastikan saldo awal mencukupi di dalam transaksi uji & catat saldo awal (Tahap 1)
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $strawberry->id],
            ['current_stock' => 10000, 'reserved_stock' => 0, 'available_stock' => 10000, 'unit' => 'GR']
        );
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $gulaPasir->id],
            ['current_stock' => 25000, 'reserved_stock' => 0, 'available_stock' => 25000, 'unit' => 'GR']
        );
        InventoryStock::updateOrCreate(
            ['outlet_id' => $mejasem->id, 'inventory_item_id' => $strawberrySyrup->id],
            ['current_stock' => 5000, 'reserved_stock' => 0, 'available_stock' => 5000, 'unit' => 'GR']
        );

        $stockAt = fn (InventoryItem $item): float => (float) (InventoryStock::where('outlet_id', $mejasem->id)
            ->where('inventory_item_id', $item->id)
            ->value('current_stock') ?? 0);

        $stage1 = [
            'strawberry' => $stockAt($strawberry),
            'gula' => $stockAt($gulaPasir),
            'syrup' => $stockAt($strawberrySyrup),
        ];

        $operation = DivisionDailyOperation::firstOrCreate(
            ['outlet_id' => $mejasem->id, 'division_id' => $divisionId, 'operation_date' => today()],
            ['opened_by' => $staff->id, 'opened_at' => now(), 'status' => 'open']
        );

        // Siapkan dokumen operasional submitted (pending approval) dari Staff
        $mr = MaterialRequest::create([
            'request_number' => 'MR-SIM-MGR-' . uniqid(),
            'division_daily_operation_id' => $operation->id,
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'requested_by' => $staff->id,
            'request_date' => today(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $mr->items()->create(['inventory_item_id' => $strawberry->id, 'requested_qty' => 1000, 'unit' => 'GR']);
        $mr->items()->create(['inventory_item_id' => $gulaPasir->id, 'requested_qty' => 500, 'unit' => 'GR']);

        $pr = ProductionRecord::create([
            'production_number' => 'PRD-SIM-MGR-' . uniqid(),
            'division_daily_operation_id' => $operation->id,
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'produced_by' => $staff->id,
            'production_date' => today(),
            'production_type' => ProductionRecord::TYPES[0],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $pr->items()->create([
            'inventory_item_id' => $strawberrySyrup->id,
            'item_name' => $strawberrySyrup->name,
            'item_type' => 'wip',
            'qty' => 1500,
            'unit' => 'GR',
        ]);

        $wr = WasteRecord::create([
            'waste_number' => 'WR-SIM-MGR-' . uniqid(),
            'division_daily_operation_id' => $operation->id,
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'recorded_by' => $staff->id,
            'waste_date' => today(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $wr->items()->create([
            'inventory_item_id' => $strawberry->id,
            'item_name' => $strawberry->name,
            'item_type' => 'raw_material',
            'qty' => 50,
            'unit' => 'GR',
            'reason' => 'damaged',
        ]);

        // 2a. Approve Take Bahan (+1.000 GR Strawberry, +500 GR Gula Pasir)
        $this->actingAs($manager)
            ->post(route('manager.material-requests.issue-stock', $mr), ['mode' => 'restock_in'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(round($stage1['strawberry'] + 1000, 2), round($stockAt($strawberry), 2));
        $this->assertEquals(round($stage1['gula'] + 500, 2), round($stockAt($gulaPasir), 2));

        // 2b. Approve Input Produksi WIP (+1.500 GR Strawberry Syrup & potong BOM: -1.000 GR Strawberry, -500 GR Gula Pasir)
        $this->actingAs($manager)
            ->post(route('manager.production-records.apply-stock', $pr), ['deduct_bom' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(round($stage1['syrup'] + 1500, 2), round($stockAt($strawberrySyrup), 2));
        $this->assertEquals(round($stage1['strawberry'], 2), round($stockAt($strawberry), 2));
        $this->assertEquals(round($stage1['gula'], 2), round($stockAt($gulaPasir), 2));

        // 2c. Approve Waste (-50 GR Frozen Strawberry)
        $this->actingAs($manager)
            ->post(route('manager.waste-records.apply-stock', $wr))
            ->assertRedirect()
            ->assertSessionHas('success');

        $stage2 = [
            'strawberry' => $stockAt($strawberry),
            'gula' => $stockAt($gulaPasir),
            'syrup' => $stockAt($strawberrySyrup),
        ];

        $this->assertEquals(round($stage1['strawberry'] - 50, 2), round($stage2['strawberry'], 2));
        $this->assertEquals(round($stage1['gula'], 2), round($stage2['gula'], 2));
        $this->assertEquals(round($stage1['syrup'] + 1500, 2), round($stage2['syrup'], 2));

        // 3. Impor Pemakaian Penjualan ESB via rute Manager (/manager/esb-imports)
        $salesFile = storage_path('app/esb/Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx');
        $this->assertFileExists($salesFile);

        $uploaded = new UploadedFile(
            $salesFile,
            'Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $previewRes = $this->actingAs($manager)->post(route('manager.esb-imports.preview'), [
            'outlet_id' => $mejasem->id,
            'report_date' => today()->toDateString(),
            'esb_file' => $uploaded,
        ]);
        $previewRes->assertOk();

        $previewToken = $previewRes->viewData('previewToken');
        $matchedRows = $previewRes->viewData('matchedRows');
        $this->assertNotEmpty($matchedRows);

        $include = [];
        foreach ($matchedRows as $r) {
            $include[$r['index']] = '1';
        }

        $this->actingAs($manager)->post(route('manager.esb-imports.execute'), [
            'preview_token' => $previewToken,
            'include' => $include,
            'notes' => 'Simulasi Impor ESB Manager - Mejasem',
        ])->assertRedirect(route('manager.esb-imports.index'))->assertSessionHas('success');

        $esbBatch = EsbSalesImport::latest('id')->firstOrFail();
        $esbMovementsCount = StockMovement::where('reference_type', EsbSalesImport::class)
            ->where('reference_id', $esbBatch->id)
            ->where('movement_type', 'esb_sales_out')
            ->count();
        $this->assertGreaterThan(0, $esbMovementsCount);

        $stage3 = [
            'strawberry' => $stockAt($strawberry),
            'gula' => $stockAt($gulaPasir),
            'syrup' => $stockAt($strawberrySyrup),
        ];

        // Pastikan pemotongan ESB memang tercatat dan mengubah stok (setidaknya untuk item yang ada di laporan ESB, mis. GULA PASIR / STRAWBERRY SYRUP)
        $esbDeductedGula = round($stage2['gula'] - $stage3['gula'], 2);
        $esbDeductedSyrup = round($stage2['syrup'] - $stage3['syrup'], 2);
        $this->assertTrue($esbDeductedGula > 0 || $esbDeductedSyrup > 0 || $esbMovementsCount > 0);

        // 4. Review & Approval Stock Opname (sengaja buat selisih -20 GR pada FROZEN STRAWBERRY)
        $targetPhysicalStrawberry = round($stage3['strawberry'] - 20.0, 2);
        $targetPhysicalGula = round($stage3['gula'], 2);
        $targetPhysicalSyrup = round($stage3['syrup'], 2);

        $strawberryCost = (float) ($strawberry->unit_cost > 0 ? $strawberry->unit_cost : ($strawberry->last_purchase_price ?? 0));
        $gulaCost = (float) ($gulaPasir->unit_cost > 0 ? $gulaPasir->unit_cost : ($gulaPasir->last_purchase_price ?? 0));
        $syrupCost = (float) ($strawberrySyrup->unit_cost > 0 ? $strawberrySyrup->unit_cost : ($strawberrySyrup->last_purchase_price ?? 0));

        $so = StockOpname::create([
            'opname_number' => 'SO-SIM-MGR-' . uniqid(),
            'division_daily_operation_id' => $operation->id,
            'outlet_id' => $mejasem->id,
            'division_id' => $divisionId,
            'counted_by' => $staff->id,
            'opname_date' => today(),
            'opname_type' => 'daily_closing',
            'is_blind_count' => true,
            'status' => 'submitted',
            'submitted_at' => now(),
            'total_variance_value' => round(-20.0 * $strawberryCost, 2),
        ]);

        $soItemStrawberry = $so->items()->create([
            'inventory_item_id' => $strawberry->id,
            'system_stock' => $stage3['strawberry'],
            'physical_stock' => $targetPhysicalStrawberry,
            'difference_qty' => -20.0,
            'unit_cost' => $strawberryCost,
            'variance_cost' => round(-20.0 * $strawberryCost, 2),
            'variance_value' => round(-20.0 * $strawberryCost, 2),
            'unit' => 'GR',
        ]);
        $soItemGula = $so->items()->create([
            'inventory_item_id' => $gulaPasir->id,
            'system_stock' => $stage3['gula'],
            'physical_stock' => $targetPhysicalGula,
            'difference_qty' => 0,
            'unit_cost' => $gulaCost,
            'variance_cost' => 0,
            'variance_value' => 0,
            'unit' => 'GR',
        ]);
        $soItemSyrup = $so->items()->create([
            'inventory_item_id' => $strawberrySyrup->id,
            'system_stock' => $stage3['syrup'],
            'physical_stock' => $targetPhysicalSyrup,
            'difference_qty' => 0,
            'unit_cost' => $syrupCost,
            'variance_cost' => 0,
            'variance_value' => 0,
            'unit' => 'GR',
        ]);

        // Buka halaman review detail Stock Opname
        $this->actingAs($manager)
            ->get(route('manager.stock-opnames.show', $so))
            ->assertOk()
            ->assertSee('Rincian Item Opname');

        // Klik Approve & Sesuaikan Stok
        $this->actingAs($manager)
            ->post(route('manager.stock-opnames.review', $so), [
                'review_notes' => 'Disetujui dalam simulasi Manager - selisih -20 GR susut alami.',
                'items' => [
                    $soItemStrawberry->id => [
                        'physical_stock' => $targetPhysicalStrawberry,
                        'variance_reason' => 'shrinkage',
                        'notes' => 'Susut pencairan buah beku',
                    ],
                    $soItemGula->id => [
                        'physical_stock' => $targetPhysicalGula,
                        'variance_reason' => null,
                        'notes' => null,
                    ],
                    $soItemSyrup->id => [
                        'physical_stock' => $targetPhysicalSyrup,
                        'variance_reason' => null,
                        'notes' => null,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 5. Verifikasi status reviewed, variance_qty & variance_cost (Rp), serta saldo akhir = 100% angka fisik
        $so->refresh();
        $soItemStrawberry->refresh();

        $this->assertSame('reviewed', $so->status);
        $this->assertEquals(-20.0, round((float) $soItemStrawberry->difference_qty, 2));
        $this->assertEquals(round(-20.0 * $strawberryCost, 2), round((float) $soItemStrawberry->variance_cost, 2));

        $stage4 = [
            'strawberry' => $stockAt($strawberry),
            'gula' => $stockAt($gulaPasir),
            'syrup' => $stockAt($strawberrySyrup),
        ];

        $this->assertEquals($targetPhysicalStrawberry, round($stage4['strawberry'], 2));
        $this->assertEquals($targetPhysicalGula, round($stage4['gula'], 2));
        $this->assertEquals($targetPhysicalSyrup, round($stage4['syrup'], 2));

        // Cetak log perjalanan saldo dari Tahap 1 hingga Tahap 4 ke console
        $log = "\n"
            . "==========================================================================================================\n"
            . "  SIMULASI ROLE MANAGER — APPROVAL, IMPOR ESB, & OPNAME (OUTLET: {$mejasem->name})\n"
            . "==========================================================================================================\n"
            . sprintf("  Manager    : %s (%s)\n", $manager->name, $manager->email)
            . sprintf("  ESB Batch  : #%d (%d item dipotong via esb_sales_out)\n", $esbBatch->id, $esbMovementsCount)
            . sprintf(
                "  Opname     : %s [Status: %s] | Selisih Strawberry: %.2f GR (Rp %s)\n",
                $so->opname_number,
                strtoupper($so->status),
                (float) $soItemStrawberry->difference_qty,
                number_format((float) $soItemStrawberry->variance_cost, 2, ',', '.')
            )
            . "----------------------------------------------------------------------------------------------------------\n"
            . sprintf("  %-20s | %14s | %14s | %14s | %14s\n", 'Item', '1. Awal', '2. Post-Approve', '3. Post-ESB', '4. Post-Opname')
            . "----------------------------------------------------------------------------------------------------------\n"
            . sprintf(
                "  %-20s | %11s GR | %11s GR | %11s GR | %11s GR\n",
                $strawberry->name,
                number_format($stage1['strawberry'], 2, ',', '.'),
                number_format($stage2['strawberry'], 2, ',', '.'),
                number_format($stage3['strawberry'], 2, ',', '.'),
                number_format($stage4['strawberry'], 2, ',', '.')
            )
            . sprintf(
                "  %-20s | %11s GR | %11s GR | %11s GR | %11s GR\n",
                $gulaPasir->name,
                number_format($stage1['gula'], 2, ',', '.'),
                number_format($stage2['gula'], 2, ',', '.'),
                number_format($stage3['gula'], 2, ',', '.'),
                number_format($stage4['gula'], 2, ',', '.')
            )
            . sprintf(
                "  %-20s | %11s GR | %11s GR | %11s GR | %11s GR\n",
                $strawberrySyrup->name,
                number_format($stage1['syrup'], 2, ',', '.'),
                number_format($stage2['syrup'], 2, ',', '.'),
                number_format($stage3['syrup'], 2, ',', '.'),
                number_format($stage4['syrup'], 2, ',', '.')
            )
            . "==========================================================================================================\n";

        fwrite(STDOUT, $log);
    }
}
