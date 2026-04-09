<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

// Fetch Messages
$messages = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white/50">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Customer Inquiries</h3>
            <p class="text-xs text-gray-500 font-medium">Manage and respond to website messages</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Sender</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Message Preview</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Date</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="4" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-envelope-open-text"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No messages found</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($messages as $msg): ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0">
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$msg['name']) ?></p>
                        <p class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars((string)$msg['email']) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[11px] text-gray-500 line-clamp-1 max-w-xs mb-0 italic">"<?= htmlspecialchars((string)$msg['message']) ?>..."</p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[11px] text-gray-400 font-bold mb-0"><?= date('M d, Y', strtotime($msg['created_at'])) ?></p>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <div class="flex justify-end gap-2">
                            <button class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm" title="View & Reply" data-bs-toggle="modal" data-bs-target="#msgModal<?= $msg['id'] ?>">
                                <i class="fas fa-reply text-sm"></i>
                            </button>
                            <form action="delete_message.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" class="inline">
                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-500 transition-all shadow-sm" title="Delete">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- Modal -->
                <div class="modal fade" id="msgModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-[24px] shadow-2xl overflow-hidden">
                            <div class="modal-header bg-primary text-white border-0 p-6">
                                <h5 class="modal-title font-black uppercase tracking-tight text-sm">Message from <?= htmlspecialchars((string)$msg['name']) ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-8">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black">
                                        <?= substr($msg['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$msg['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string)$msg['email']) ?></p>
                                    </div>
                                </div>
                                <div class="p-5 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-sm text-gray-600 leading-relaxed italic mb-0">"<?= nl2br(htmlspecialchars((string)$msg['message'])) ?>"</p>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-6 bg-gray-50/50">
                                <button type="button" class="btn btn-outline !py-2 !px-6 text-[10px] font-black uppercase tracking-widest" data-bs-dismiss="modal">Close</button>
                                <a href="mailto:<?= htmlspecialchars((string)$msg['email']) ?>" class="btn btn-primary !py-2 !px-6 text-[10px] font-black uppercase tracking-widest no-underline">Reply via Email</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
