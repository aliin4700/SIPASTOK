<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\EsbSalesImport;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\EsbReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EsbImportFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_esb_parsers_accurately_read_real_esb_excel_files(): void
    {
        $service = app(EsbReportService::class);

        $salesFile = storage_path('app/esb/Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx');
        $this->assertFileExists($salesFile);

        $salesParsed = $service->parseSalesMaterialUsage($salesFile);
        $this->assertSame(10, $salesParsed['header_row']);
        $this->assertGreaterThan(200, count($salesParsed['rows']));
        $this->assertSame('ACAR CABE', $salesParsed['rows'][0]['product_name']);
        $this->assertEquals(5100.0, $salesParsed['rows'][0]['qty']);

        $bomFile = storage_path('app/esb/Bill Of Material Report_MJCTADMINERP_17957676849666.xlsx');
        $this->assertFileExists($bomFile);

        $bomParsed = $service->parseBillOfMaterial($bomFile);
        $this->assertSame(9, $bomParsed['header_row']);
        $this->assertGreaterThan(50, count($bomParsed['formulas']));
        $this->assertSame('Assembly', $bomParsed['formulas'][0]['formula_type']);
        $this->assertEquals(1500.0, $bomParsed['formulas'][0]['output_qty']);
        $this->assertSame('GR', $bomParsed['formulas'][0]['output_unit']);
    }

    public function test_manager_and_admin_can_access_esb_import_pages(): void
    {
        $staff = User::where('role', 'staff_outlet')->first();
        $manager = User::where('role', 'manager')->first();
        $admin = User::whereIn('role', ['admin', 'super_admin'])->first();

        $this->assertNotNull($staff);
        $this->assertNotNull($manager);
        $this->assertNotNull($admin);

        // Staff Outlet tidak boleh mengakses fitur Impor ESB
        $this->actingAs($staff)
            ->get('/staff/esb-imports')
            ->assertNotFound();

        $this->actingAs($staff)
            ->get(route('manager.esb-imports.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('backend.esb-imports.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('manager.esb-imports.index'))
            ->assertOk()
            ->assertSee('Impor Pemakaian Bahan ESB POS');

        $this->actingAs($admin)
            ->get(route('backend.esb-imports.index'))
            ->assertOk()
            ->assertSee('Impor Pemakaian Bahan ESB POS');

        $quickName = 'ESB QUICK ITEM ' . uniqid();
        $this->actingAs($manager)
            ->postJson(route('manager.esb-imports.quick-create-item'), [
                'name' => $quickName,
                'unit' => 'GR',
                'category' => 'Raw Material',
                'unit_cost' => 125.5,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'created')
            ->assertJsonPath('item.name', $quickName)
            ->assertJsonPath('item.base_unit', 'GR');
    }

    public function test_esb_sales_usage_preview_execute_deducts_stock_and_prevents_duplicates(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $outlet = Outlet::where('is_active', true)->firstOrFail();
        $division = Division::where('is_active', true)->firstOrFail();

        $manualTarget = InventoryItem::create([
            'code' => 'TEST-ESB-' . uniqid(),
            'name' => 'TEST ESB MANUAL TARGET ' . uniqid(),
            'item_type' => 'raw_material',
            'division_id' => $division->id,
            'base_unit' => 'GR',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        InventoryStock::updateOrCreate(
            ['outlet_id' => $outlet->id, 'inventory_item_id' => $manualTarget->id],
            ['current_stock' => 1000, 'reserved_stock' => 0, 'available_stock' => 1000, 'unit' => 'GR']
        );

        $salesFile = storage_path('app/esb/Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx');
        $uploaded = new UploadedFile(
            $salesFile,
            'Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $reportDate = '2026-11-27';

        $previewResponse = $this->actingAs($manager)->post(route('manager.esb-imports.preview'), [
            'outlet_id' => $outlet->id,
            'report_date' => $reportDate,
            'esb_file' => $uploaded,
        ]);

        $previewResponse->assertOk()->assertSee('Preview &amp; Mapping Impor Pemakaian ESB', false);

        $previewToken = $previewResponse->viewData('previewToken');
        $matchedRows = $previewResponse->viewData('matchedRows');
        $unmatchedRows = $previewResponse->viewData('unmatchedRows');

        $this->assertNotEmpty($previewToken);

        $include = [];
        if (! empty($matchedRows)) {
            $firstMatched = $matchedRows[0];
            $include[$firstMatched['index']] = '1';
        }

        $mappings = [];
        $mappedQty = 0.0;
        if (! empty($unmatchedRows)) {
            $firstUnmatched = $unmatchedRows[0];
            $mappings[$firstUnmatched['index']] = $manualTarget->id;
            $mappedQty = (float) $firstUnmatched['qty'];
        }

        $executeResponse = $this->actingAs($manager)->post(route('manager.esb-imports.execute'), [
            'preview_token' => $previewToken,
            'include' => $include,
            'mappings' => $mappings,
            'notes' => 'Uji impor ESB otomatis',
        ]);

        $executeResponse->assertRedirect(route('manager.esb-imports.index'));
        $executeResponse->assertSessionHas('success');

        $batch = EsbSalesImport::latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame($outlet->id, (int) $batch->outlet_id);

        if ($mappedQty > 0) {
            $updatedStock = InventoryStock::where('outlet_id', $outlet->id)
                ->where('inventory_item_id', $manualTarget->id)
                ->first();

            $expectedAfter = max(0.0, 1000.0 - round($mappedQty, 2));
            $this->assertEquals($expectedAfter, (float) $updatedStock->current_stock);

            $movement = StockMovement::where('reference_type', EsbSalesImport::class)
                ->where('reference_id', $batch->id)
                ->where('inventory_item_id', $manualTarget->id)
                ->first();

            $this->assertNotNull($movement);
            $this->assertSame('esb_sales_out', $movement->movement_type);
        }

        // Upload file yang sama untuk outlet & tanggal sama -> harus terdeteksi duplikat
        $uploaded2 = new UploadedFile(
            $salesFile,
            'Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $dupPreview = $this->actingAs($manager)->post(route('manager.esb-imports.preview'), [
            'outlet_id' => $outlet->id,
            'report_date' => $reportDate,
            'esb_file' => $uploaded2,
        ]);

        $dupPreview->assertOk()->assertSee('Peringatan Duplikasi');
        $dupToken = $dupPreview->viewData('previewToken');

        $dupExecute = $this->actingAs($manager)->post(route('manager.esb-imports.execute'), [
            'preview_token' => $dupToken,
            'include' => $include,
        ]);

        $dupExecute->assertRedirect(route('manager.esb-imports.index'));
        $dupExecute->assertSessionHas('error');
    }
}
