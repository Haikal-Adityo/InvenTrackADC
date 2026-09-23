<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StuffRequest;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        DB::table('stock_request_lines')->truncate();
        DB::table('stock_requests')->truncate();
        DB::table('stuff_request_items')->truncate();
        DB::table('stuff_requests')->truncate();
        DB::table('transactions')->truncate();
        DB::table('items')->truncate();
        DB::table('users')->truncate();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $users = $this->seedUsers();
        $items = $this->seedItems();
        $this->seedTransactions($users, $items);
        $this->seedStuffRequests($users, $items);
        $this->seedStockRequests($users, $items);
    }

    private function seedUsers(): array
    {
        $defaultPassword = 'adc.password';
        $password = Hash::make($defaultPassword);
        $rows = [
            ['username' => 'superadmin', 'name' => 'Superadmin Inventory', 'email' => 'superadmin@inventory.com', 'role' => 'superadmin', 'bidang' => null, 'no_hp' => '081200000000'],
            ['username' => 'admin.umum', 'name' => 'Admin Umum', 'email' => 'admin.umum@inventory.com', 'role' => 'admin', 'bidang' => 'umum', 'no_hp' => '081200000101'],
            ['username' => 'manager.umum', 'name' => 'Manajer Umum', 'email' => 'manager.umum@inventory.com', 'role' => 'manajer', 'bidang' => 'umum', 'no_hp' => '081200000102'],
            ['username' => 'staf.umum', 'name' => 'Staf Umum', 'email' => 'staf.umum@inventory.com', 'role' => 'staf', 'bidang' => 'umum', 'no_hp' => '081200000103'],
            ['username' => 'admin.teknik', 'name' => 'Admin Teknik', 'email' => 'admin.teknik@inventory.com', 'role' => 'admin', 'bidang' => 'teknik', 'no_hp' => '081200000201'],
            ['username' => 'manager.teknik', 'name' => 'Manajer Teknik', 'email' => 'manager.teknik@inventory.com', 'role' => 'manajer', 'bidang' => 'teknik', 'no_hp' => '081200000202'],
            ['username' => 'pending.umum', 'name' => 'Calon Staf Umum', 'email' => 'pending.umum@inventory.com', 'role' => 'staf', 'bidang' => 'umum', 'no_hp' => '081200000104', 'account_status' => 'pending'],
        ];

        $users = [];
        foreach ($rows as $row) {
            $users[$row['email']] = User::create($row + [
                'password' => $password,
                'account_status' => $row['account_status'] ?? 'approved',
            ]);
        }

        return $users;
    }

    private function seedItems(): array
    {
        $rows = [
            ['name' => 'Kertas HVS A4 70gr', 'category' => 'ATK', 'unit' => 'Rim', 'bidang' => 'umum', 'min_stock' => 10],
            ['name' => 'Pulpen Pilot BPS-GP', 'category' => 'ATK', 'unit' => 'Lusin', 'bidang' => 'umum', 'min_stock' => 4],
            ['name' => 'Tinta Printer Black', 'category' => 'ATK', 'unit' => 'Botol', 'bidang' => 'umum', 'min_stock' => 5],
            ['name' => 'Map L Folder', 'category' => 'Arsip', 'unit' => 'Pcs', 'bidang' => 'umum', 'min_stock' => 30],
            ['name' => 'Sabun Cuci Tangan 500ml', 'category' => 'Kebersihan', 'unit' => 'Botol', 'bidang' => 'umum', 'min_stock' => 12],
            ['name' => 'Kabel LAN Cat6 305m', 'no_normalisasi' => 'SU-01-LAN-001', 'category' => 'Spare Part', 'component' => 'Jaringan', 'unit' => 'Box', 'bidang' => 'teknik', 'lokasi' => 'Gudang Teknik A1', 'volume' => 25, 'ship_unloader' => null, 'min_stock' => 2],
            ['name' => 'RJ45 Connector', 'no_normalisasi' => 'SU-02-RJ45-002', 'category' => 'Spare Part', 'component' => 'Jaringan', 'unit' => 'Pack', 'bidang' => 'teknik', 'lokasi' => 'Rak Komponen B2', 'volume' => 100, 'ship_unloader' => null, 'min_stock' => 5],
            ['name' => 'Switch 24 Port Gigabit', 'no_normalisasi' => 'SU-03-SW24-003', 'category' => 'Perangkat IT', 'component' => 'Jaringan', 'unit' => 'Unit', 'bidang' => 'teknik', 'lokasi' => 'Panel Room C1', 'volume' => 1, 'ship_unloader' => null, 'min_stock' => 2],
            ['name' => 'Hard Disk External 2TB', 'no_normalisasi' => 'SU-04-HDD-004', 'category' => 'Perangkat IT', 'component' => 'Storage', 'unit' => 'Unit', 'bidang' => 'teknik', 'lokasi' => 'Lemari IT D1', 'volume' => 1, 'ship_unloader' => null, 'min_stock' => 3],
            ['name' => 'Obeng Set Presisi', 'no_normalisasi' => 'SU-01-TOOLS-005', 'category' => 'Tools', 'component' => 'Tools', 'unit' => 'Set', 'bidang' => 'teknik', 'lokasi' => 'Toolbox Teknik', 'volume' => 1, 'ship_unloader' => null, 'min_stock' => 4],
        ];

        $items = [];
        foreach ($rows as $row) {
            $items[$row['name']] = Item::create($row);
        }

        return $items;
    }

    private function seedTransactions(array $users, array $items): void
    {
        $now = Carbon::now();
        $departments = [
            'umum' => [
                'staff' => $users['staf.umum@inventory.com'],
                'admin' => $users['admin.umum@inventory.com'],
                'items' => collect($items)->where('bidang', 'umum')->values(),
            ],
            'teknik' => [
                'staff' => $users['admin.teknik@inventory.com'],
                'admin' => $users['admin.teknik@inventory.com'],
                'items' => collect($items)->where('bidang', 'teknik')->values(),
            ],
        ];

        foreach ($departments as $bidang => $data) {
            foreach ($data['items'] as $index => $item) {
                $date = $now->copy()->subDays(20 - $index);
                $inQuantity = 35 + ($index * 6);
                $outQuantity = 4 + $index;
                $shipUnloader = $bidang === 'teknik'
                    ? match ($index % 5) {
                        0 => '1,2',
                        1 => '1,3',
                        2 => '3,4',
                        3 => '2,4',
                        default => '1',
                    }
                    : null;

                Transaction::create([
                    'item_id' => $item->id,
                    'user_id' => $data['staff']->id,
                    'bidang' => $bidang,
                    'no_normalisasi' => $item->no_normalisasi,
                    'lokasi' => $item->lokasi,
                    'volume' => $bidang === 'teknik' ? $item->volume : null,
                    'ship_unloader' => $shipUnloader,
                    'date' => $date->toDateString(),
                    'type' => 'in',
                    'quantity' => $inQuantity,
                    'price' => $bidang === 'umum' ? 15000 + ($index * 2500) : null,
                    'description' => 'Stok awal Bidang ' . ucfirst($bidang),
                    'status' => 'approved',
                    'approved_by' => $data['admin']->id,
                    'approved_at' => $date->copy()->addHour(),
                ]);

                Transaction::create([
                    'item_id' => $item->id,
                    'user_id' => $data['staff']->id,
                    'bidang' => $bidang,
                    'no_normalisasi' => $item->no_normalisasi,
                    'lokasi' => $item->lokasi,
                    'volume' => $bidang === 'teknik' ? $item->volume : null,
                    'ship_unloader' => $shipUnloader,
                    'date' => $date->copy()->addDays(5)->toDateString(),
                    'type' => 'out',
                    'quantity' => $outQuantity,
                    'price' => null,
                    'description' => 'Pemakaian operasional Bidang ' . ucfirst($bidang),
                    'status' => 'approved',
                    'approved_by' => $data['admin']->id,
                    'approved_at' => $date->copy()->addDays(5)->addHour(),
                ]);
            }
        }
    }

    private function seedStuffRequests(array $users, array $items): void
    {
        $samples = [
            [
                'bidang' => 'umum',
                'admin' => $users['admin.umum@inventory.com'],
                'request' => ['requester_name' => 'Rina Administrasi', 'nip' => '198801012020012001', 'jabatan' => 'Administrasi', 'notes' => 'Kebutuhan rapat mingguan.'],
                'lines' => ['Kertas HVS A4 70gr' => 2, 'Pulpen Pilot BPS-GP' => 1],
            ],
            [
                'bidang' => 'teknik',
                'admin' => $users['admin.teknik@inventory.com'],
                'request' => ['requester_name' => 'Dimas Teknisi', 'nip' => '199002022021021002', 'jabatan' => 'Teknisi Jaringan', 'notes' => 'Penggantian perangkat jaringan lantai 2.'],
                'lines' => ['Kabel LAN Cat6 305m' => 1, 'RJ45 Connector' => 2],
            ],
        ];

        foreach ($samples as $sample) {
            $stuffRequest = StuffRequest::create($sample['request'] + [
                'bidang' => $sample['bidang'],
                'status' => 'pending',
            ]);

            foreach ($sample['lines'] as $itemName => $quantity) {
                $stuffRequest->lines()->create([
                    'item_id' => $items[$itemName]->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    private function seedStockRequests(array $users, array $items): void
    {
        $samples = [
            ['user' => $users['staf.umum@inventory.com'], 'item' => $items['Map L Folder'], 'quantity' => 50, 'price' => 1800],
            ['user' => $users['admin.teknik@inventory.com'], 'item' => $items['Switch 24 Port Gigabit'], 'quantity' => 2, 'price' => 1450000],
        ];

        foreach ($samples as $sample) {
            $stockRequest = StockRequest::create([
                'user_id' => $sample['user']->id,
                'bidang' => $sample['user']->bidang,
                'category' => $sample['item']->category,
                'status' => 'pending',
            ]);

            $stockRequest->lines()->create([
                'item_id' => $sample['item']->id,
                'quantity' => $sample['quantity'],
                'price' => $sample['price'],
                'category' => $sample['item']->category,
                'description' => 'Permintaan stok ulang Bidang ' . ucfirst($sample['user']->bidang),
            ]);
        }
    }
}
