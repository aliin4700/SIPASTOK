# Import Master Items

Command:

```bash
php artisan breadlab:import-master-items --dry-run
php artisan breadlab:import-master-items
php artisan breadlab:import-master-items --force-stock
```

Default file:

```text
storage/app/imports/breadlab_master_item_sementara.xlsx
```

Fallback CSV files:

```text
storage/app/imports/inventory_items.csv
storage/app/imports/inventory_stocks_seed.csv
```

Rules:

- Items use `code` as primary lookup. If code is empty, lookup uses `name + division_id`.
- Stocks use `outlet_id + inventory_item_id`.
- Existing `current_stock > 0` is not overwritten unless `--force-stock` is used.
- `--dry-run` writes inside a transaction and rolls back.
