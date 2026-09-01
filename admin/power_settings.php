<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Power Entities';
$message = null;

// Ensure ha_entities table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ha_entities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_key VARCHAR(100) NOT NULL UNIQUE,
        entity_id  VARCHAR(200) NOT NULL,
        friendly_name VARCHAR(100) DEFAULT NULL,
        entity_type ENUM('sensor','switch','light','binary_sensor','automation','scene','other') DEFAULT 'sensor',
        site ENUM('shop','home','global') DEFAULT 'shop',
        display_unit VARCHAR(20) DEFAULT NULL,
        show_in_control TINYINT(1) DEFAULT 0,
        show_in_power   TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch(Exception $e) {}

// Add new entity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entity'])) {
    $data = [
        sanitize($_POST['entity_key']),
        sanitize($_POST['entity_id']),
        sanitize($_POST['friendly_name']),
        sanitize($_POST['entity_type']),
        sanitize($_POST['site']),
        sanitize($_POST['display_unit']),
        isset($_POST['show_in_control']) ? 1 : 0,
        isset($_POST['show_in_power'])   ? 1 : 0,
        (int)($_POST['display_order'] ?? 0),
    ];
    try {
        $stmt = $pdo->prepare("INSERT INTO ha_entities (entity_key, entity_id, friendly_name, entity_type, site, display_unit, show_in_control, show_in_power, display_order)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($data);
        $message = ['type' => 'success', 'text' => 'Entity added!'];
    } catch(PDOException $e) {
        $message = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
    }
}

// Update entity inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_entity'])) {
    $id = (int)$_POST['entity_id_pk'];
    $stmt = $pdo->prepare("UPDATE ha_entities SET entity_id=?, friendly_name=?, entity_type=?, site=?, display_unit=?, show_in_control=?, show_in_power=?, display_order=? WHERE id=?");
    $stmt->execute([
        sanitize($_POST['entity_id']),
        sanitize($_POST['friendly_name']),
        sanitize($_POST['entity_type']),
        sanitize($_POST['site']),
        sanitize($_POST['display_unit']),
        isset($_POST['show_in_control']) ? 1 : 0,
        isset($_POST['show_in_power'])   ? 1 : 0,
        (int)($_POST['display_order'] ?? 0),
        $id
    ]);
    $message = ['type' => 'success', 'text' => 'Entity updated!'];
}

// Delete entity
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM ha_entities WHERE id=?")->execute([$id]);
    $message = ['type' => 'success', 'text' => 'Entity deleted.'];
}

// Fetch all entities
$entities = $pdo->query("SELECT * FROM ha_entities ORDER BY site, display_order, id")->fetchAll();
$shopEntities = array_filter($entities, fn($e) => $e['site'] === 'shop');
$homeEntities = array_filter($entities, fn($e) => $e['site'] === 'home');
$globalEntities = array_filter($entities, fn($e) => $e['site'] === 'global');
?>
<?php include 'includes/admin_head.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <?php if ($message): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-3
        <?php echo $message['type'] === 'success' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?>">
        <i class="fa-solid <?php echo $message['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
        <?php echo $message['text']; ?>
    </div>
    <?php endif; ?>

    <!-- Add Entity Form -->
    <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-yellow-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-plus text-yellow-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">Add New Entity</h2>
            </div>
            <button onclick="document.getElementById('add-form').classList.toggle('hidden')"
                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-xs font-medium transition">
                <i class="fa-solid fa-chevron-down"></i> Toggle
            </button>
        </div>
        <div id="add-form" class="p-6 hidden">
            <form method="POST" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <input type="hidden" name="add_entity" value="1">
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Entity Key <span class="text-red-400">*</span></label>
                    <input type="text" name="entity_key" required
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm font-mono"
                        placeholder="shop_pv">
                    <p class="text-[9px] text-gray-600 mt-1">JS variable name (no spaces)</p>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">HA Entity ID <span class="text-red-400">*</span></label>
                    <input type="text" name="entity_id" required
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm font-mono"
                        placeholder="sensor.flin_energy_pv_power">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Friendly Name</label>
                    <input type="text" name="friendly_name"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                        placeholder="Shop Solar PV">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Type</label>
                    <select name="entity_type" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                        <option value="sensor">sensor</option>
                        <option value="switch">switch</option>
                        <option value="light">light</option>
                        <option value="binary_sensor">binary_sensor</option>
                        <option value="automation">automation</option>
                        <option value="scene">scene</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Site</label>
                    <select name="site" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                        <option value="shop">Shop (Site A)</option>
                        <option value="home">Home (Site B)</option>
                        <option value="global">Global</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Unit</label>
                    <input type="text" name="display_unit"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                        placeholder="W, %, A, °C">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Order</label>
                    <input type="number" name="display_order" value="0"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Visibility</label>
                    <div class="flex gap-4 pt-1">
                        <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                            <input type="checkbox" name="show_in_power" checked class="accent-blue-500"> Power
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                            <input type="checkbox" name="show_in_control" class="accent-green-500"> Control
                        </label>
                    </div>
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="px-6 py-2.5 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-xl transition text-sm">
                        <i class="fa-solid fa-plus mr-1"></i> Add Entity
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Entity Tables by Site -->
    <?php foreach ([['shop', 'Shop (Site A)', 'blue', $shopEntities], ['home', 'Home (Site B)', 'purple', $homeEntities], ['global', 'Global', 'gray', $globalEntities]] as [$site, $label, $color, $siteEntities]): ?>
    <?php if (!empty($siteEntities)): ?>
    <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden mb-4">
        <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-<?php echo $color; ?>-400"></div>
            <h3 class="font-bold text-white"><?php echo $label; ?> <span class="text-gray-500 font-normal text-sm">(<?php echo count($siteEntities); ?> entities)</span></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3 text-left font-bold">Key</th>
                        <th class="px-4 py-3 text-left font-bold">Entity ID</th>
                        <th class="px-4 py-3 text-left font-bold">Name</th>
                        <th class="px-4 py-3 text-left font-bold">Type</th>
                        <th class="px-4 py-3 text-left font-bold">Unit</th>
                        <th class="px-4 py-3 text-center font-bold">Power</th>
                        <th class="px-4 py-3 text-center font-bold">Control</th>
                        <th class="px-4 py-3 text-left font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    <?php foreach ($siteEntities as $e): ?>
                    <tr class="hover:bg-gray-800/20 transition" id="row-<?php echo $e['id']; ?>">
                        <td class="px-4 py-3 font-mono text-xs text-gray-400"><?php echo htmlspecialchars($e['entity_key']); ?></td>
                        <td class="px-4 py-3 font-mono text-xs text-blue-300 max-w-[200px] truncate"><?php echo htmlspecialchars($e['entity_id']); ?></td>
                        <td class="px-4 py-3 text-white text-xs"><?php echo htmlspecialchars($e['friendly_name'] ?? '—'); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase
                                <?php echo match($e['entity_type']) {
                                    'sensor' => 'bg-blue-500/10 text-blue-400',
                                    'switch' => 'bg-green-500/10 text-green-400',
                                    'light'  => 'bg-yellow-500/10 text-yellow-400',
                                    'binary_sensor' => 'bg-purple-500/10 text-purple-400',
                                    'automation','scene' => 'bg-orange-500/10 text-orange-400',
                                    default  => 'bg-gray-500/10 text-gray-400'
                                }; ?>">
                                <?php echo $e['entity_type']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?php echo htmlspecialchars($e['display_unit'] ?? '—'); ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php echo $e['show_in_power'] ? '<i class="fa-solid fa-check text-green-400 text-xs"></i>' : '<i class="fa-solid fa-minus text-gray-700 text-xs"></i>'; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php echo $e['show_in_control'] ? '<i class="fa-solid fa-check text-green-400 text-xs"></i>' : '<i class="fa-solid fa-minus text-gray-700 text-xs"></i>'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1.5">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($e)); ?>)"
                                    class="px-2.5 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-xs transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="?delete=<?php echo $e['id']; ?>" onclick="return confirm('Delete this entity?')"
                                    class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs transition">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm p-4">
    <div class="glass-card rounded-2xl border border-gray-700 w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-white">Edit Entity</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="update_entity" value="1">
            <input type="hidden" name="entity_id_pk" id="edit-id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">HA Entity ID</label>
                    <input type="text" name="entity_id" id="edit-entity-id"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Friendly Name</label>
                    <input type="text" name="friendly_name" id="edit-friendly"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Unit</label>
                    <input type="text" name="display_unit" id="edit-unit"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Type</label>
                    <select name="entity_type" id="edit-type" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                        <option value="sensor">sensor</option>
                        <option value="switch">switch</option>
                        <option value="light">light</option>
                        <option value="binary_sensor">binary_sensor</option>
                        <option value="automation">automation</option>
                        <option value="scene">scene</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Site</label>
                    <select name="site" id="edit-site" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                        <option value="shop">Shop</option>
                        <option value="home">Home</option>
                        <option value="global">Global</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Order</label>
                    <input type="number" name="display_order" id="edit-order"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Visibility</label>
                    <div class="flex gap-4 pt-1">
                        <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                            <input type="checkbox" name="show_in_power" id="edit-power" class="accent-blue-500"> Power
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                            <input type="checkbox" name="show_in_control" id="edit-control" class="accent-green-500"> Control
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm">
                    <i class="fa-solid fa-save mr-1"></i> Update
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 py-2.5 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition text-sm">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(e) {
        document.getElementById('edit-id').value = e.id;
        document.getElementById('edit-entity-id').value = e.entity_id;
        document.getElementById('edit-friendly').value = e.friendly_name || '';
        document.getElementById('edit-unit').value = e.display_unit || '';
        document.getElementById('edit-type').value = e.entity_type;
        document.getElementById('edit-site').value = e.site;
        document.getElementById('edit-order').value = e.display_order;
        document.getElementById('edit-power').checked = e.show_in_power == 1;
        document.getElementById('edit-control').checked = e.show_in_control == 1;
        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-modal').classList.add('flex');
    }
    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-modal').classList.remove('flex');
    }
    // Close on backdrop click
    document.getElementById('edit-modal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>

<?php include 'includes/admin_footer.php'; ?>
