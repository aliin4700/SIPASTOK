<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\User;
use App\Models\UserDivisionAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionRecordFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_production_create_prioritizes_opening_alerts_while_allowing_all_wip_recipes_and_division_items(): void
    {
        $suffix = Str::lower(Str::random(6));

        $outlet = Outlet::create([
            'name' => 'Outlet Prod ' . $suffix,
            'code' => 'OP' . strtoupper($suffix),
            'is_active' => true,
        ]);

        $division = Division::create([
            'name' => 'Divisi Prod ' . $suffix,
            'code' => 'DP' . strtoupper($suffix),
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff Prod ' . $suffix,
            'email' => "staff-prod-{$suffix}@sipastok.test",
            'password' => bcrypt('password'),
            'role' => 'staff_outlet',
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

        // 1. Item WIP yang stoknya kritis (Opening Alert)
        $alertWip = InventoryItem::create([
            'division_id' => $division->id,
            'name' => 'Simple Syrup Alert ' . $suffix,
            'code' => 'WIP-A-' . strtoupper($suffix),
            'item_type' => 'wip',
            'base_unit' => 'ml',
            'min_stock' => 1000,
            'is_active' => true,
        ]);
        InventoryStock::create([
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $alertWip->id,
            'current_stock' => 100,
            'reserved_stock' => 0,
            'available_stock' => 100,
            'unit' => 'ml',
        ]);

        // 2. Item WIP normal yang stoknya aman (bukan Opening Alert)
        $normalWip = InventoryItem::create([
            'division_id' => $division->id,
            'name' => 'Brown Sugar Syrup Aman ' . $suffix,
            'code' => 'WIP-N-' . strtoupper($suffix),
            'item_type' => 'wip',
            'base_unit' => 'ml',
            'min_stock' => 200,
            'is_active' => true,
        ]);
        InventoryStock::create([
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $normalWip->id,
            'current_stock' => 1500,
            'reserved_stock' => 0,
            'available_stock' => 1500,
            'unit' => 'ml',
        ]);

        // 3. Item Produk Jadi yang menjadi output Recipe aktif (output_inventory_item_id)
        $recipeOutputItem = InventoryItem::create([
            'division_id' => $division->id,
            'name' => 'Homemade Ginger Mix ' . $suffix,
            'code' => 'FG-R-' . strtoupper($suffix),
            'item_type' => 'finished_goods',
            'base_unit' => 'ml',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        InventoryStock::create([
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $recipeOutputItem->id,
            'current_stock' => 500,
            'reserved_stock' => 0,
            'available_stock' => 500,
            'unit' => 'ml',
        ]);

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-' . strtoupper($suffix),
            'name' => 'Resep Ginger Mix ' . $suffix,
            'output_inventory_item_id' => $recipeOutputItem->id,
            'output_qty' => 1000,
            'output_unit' => 'ml',
            'division_id' => $division->id,
            'is_active' => true,
        ]);

        // 4. Raw material divisi (tidak boleh muncul / diterima di Input Produksi)
        $rawItem = InventoryItem::create([
            'division_id' => $division->id,
            'name' => 'Tepung Mentah ' . $suffix,
            'code' => 'RM-O-' . strtoupper($suffix),
            'item_type' => 'raw_material',
            'base_unit' => 'gram',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $createResponse = $this->actingAs($staff)->get(route('staff.production.create'));
        $createResponse->assertOk()
            ->assertSee('PRIORITAS OPENING ALERT')
            ->assertSee($alertWip->name)
            ->assertSee($normalWip->name)
            ->assertSee($recipeOutputItem->name)
            ->assertDontSee($rawItem->name)
            ->assertDontSee('[ALERT');

        // Raw material ditolak pada store()
        $this->actingAs($staff)->post(route('staff.production.store'), [
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'production_type' => 'wip',
            'items' => [['inventory_item_id' => $rawItem->id, 'qty' => 250]],
        ])->assertSessionHas('error');
        $this->assertSame(0, ProductionRecord::where('outlet_id', $outlet->id)->count());

        // WIP & Produk Jadi diterima
        $storeResponse = $this->actingAs($staff)->post(route('staff.production.store'), [
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'production_type' => 'wip',
            'items' => [
                ['inventory_item_id' => $alertWip->id, 'qty' => 900],
                ['inventory_item_id' => $normalWip->id, 'qty' => 500],
                ['inventory_item_id' => $recipeOutputItem->id, 'qty' => 1000],
            ],
        ]);

        $record = ProductionRecord::where('outlet_id', $outlet->id)->latest('id')->firstOrFail();
        $storeResponse->assertRedirect(route('staff.production.show', $record));
        $this->assertCount(3, $record->items);

        // Take Bahan: hanya raw material
        $this->actingAs($staff)->get(route('staff.material-requests.create'))
            ->assertOk()
            ->assertSee($rawItem->name)
            ->assertDontSee($normalWip->name)
            ->assertDontSee($recipeOutputItem->name);

        $this->actingAs($staff)->post(route('staff.material-requests.store'), [
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'items' => [['inventory_item_id' => $normalWip->id, 'requested_qty' => 10]],
        ])->assertSessionHas('error');
        $this->assertSame(0, \App\Models\MaterialRequest::where('outlet_id', $outlet->id)->count());

        $this->actingAs($staff)->post(route('staff.material-requests.store'), [
            'outlet_id' => $outlet->id,
            'division_id' => $division->id,
            'items' => [['inventory_item_id' => $rawItem->id, 'requested_qty' => 10]],
        ])->assertSessionMissing('error');
        $this->assertSame(1, \App\Models\MaterialRequest::where('outlet_id', $outlet->id)->count());

        // Uji persetujuan (Approve) real-time memperbarui saldo inventory_stocks & idempotent
        $manager = User::create([
            'name' => 'Manager Prod ' . $suffix,
            'email' => "mgr-prod-{$suffix}@sipastok.test",
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $mr = \App\Models\MaterialRequest::where('outlet_id', $outlet->id)->firstOrFail();
        $mr->update(['status' => 'submitted', 'submitted_at' => now()]);

        $this->actingAs($manager)
            ->post(route('manager.material-requests.issue-stock', $mr))
            ->assertSessionHas('success');

        $rawStock = InventoryStock::where('outlet_id', $outlet->id)
            ->where('inventory_item_id', $rawItem->id)
            ->firstOrFail();
        $this->assertEquals(10.0, (float) $rawStock->current_stock);

        // Klik approve kedua kali tidak menggandakan stok (idempotent)
        $this->actingAs($manager)
            ->post(route('manager.material-requests.issue-stock', $mr))
            ->assertSessionHas('success');
        $this->assertEquals(10.0, (float) $rawStock->fresh()->current_stock);

        // Submit & Approve ProductionRecord -> stok WIP bertambah real-time
        $record->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->actingAs($manager)
            ->post(route('manager.production-records.apply-stock', $record))
            ->assertSessionHas('success');

        $this->assertEquals(1000.0, (float) InventoryStock::where('outlet_id', $outlet->id)->where('inventory_item_id', $alertWip->id)->value('current_stock'));
        $this->assertEquals(2000.0, (float) InventoryStock::where('outlet_id', $outlet->id)->where('inventory_item_id', $normalWip->id)->value('current_stock'));
    }
}